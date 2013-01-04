<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

require_once("Crypt/Blowfish.php");

class CipherBehavior extends ModelBehavior
{
  /** Default values of behavior.
    @key Symetric key. Default is value of 'Security.salt' configuration.
    @cipher Columns to cipher. Default is 'password'.
    @prefix Prefix of ciphered values. Default is '$E$'.
    @saltLen Length of salt as prefix and suffix. The salt ensures differend
    outputs for the same input. Default is 4.
    @padding Padding of ciphered value. Default is 4.
    @autoDecrypt Decrypt ciphered value automatically. Default is false.
    @noEncypt Disables encryption if true. Usefull for revert the encryption. */
  var $default = array(
                    'cipher' => 'password',
                    'prefix' => '$E$',
                    'saltLen' => 4,
                    'padding' => 4,
                    'autoDecrypt' => false,
                    'noEncypt' => false
                  );
  var $config = array();

  public function setup(Model $model, $config = array()) {
    $this->config[$model->name] = $this->default;

    if (isset($config['key'])) {
      $this->config[$model->name]['key'] = $config['key'];
    } else {
      $this->config[$model->name]['key'] = Configure::read('Security.salt');
    }

    if (isset($config['cipher'])) {
      $this->config[$model->name]['cipher'] = $config['cipher'];
    }

    if (isset($config['prefix'])) {
      $this->config[$model->name]['prefix'] = $config['prefix'];
    }

    if (isset($config['saltLen']) && $config['saltLen'] >= 2) {
      $this->config[$model->name]['saltLen'] = $config['saltLen'];
    }

    if (isset($config['padding']) && $config['padding'] <= 32) {
      $this->config[$model->name]['padding'] = $config['padding'];
    }

    if (isset($config['autoDecrypt'])) {
      $this->config[$model->name]['autoDecrypt'] = $config['autoDecrypt'];
    }

    if (isset($config['noEncrypt'])) {
      $this->config[$model->name]['noEncrypt'] = $config['noEncrypt'];
    }
  }

  /** Model hook to encrypt model data
    @param model Current model */
  public function beforeSave(Model $model) {
    if (isset($this->config[$model->name]) && !$this->config[$model->name]['noEncypt']) {
      if (!is_array($this->config[$model->name]['cipher'])) {
        $cipher = array($this->config[$model->name]['cipher']);
      } else {
        $cipher = $this->config[$model->name]['cipher'];
      }

      $prefix = $this->config[$model->name]['prefix'];
      $prefixLen = strlen($prefix);
      foreach ($cipher as $column) {
        if (!empty($model->data[$model->name][$column]) &&
          substr($model->data[$model->name][$column], 0, $prefixLen) != $prefix) {
          $encrypt = $this->_encryptValue($model->data[$model->name][$column], $this->config[$model->name]);
          if ($encrypt) {
            $model->data[$model->name][$column] = $encrypt;
          } else {
            $this->log(__METHOD__." Could not encrypt {$model->name}::$column: '$model->data[$model->name][$column]'");
          }
        }
      }
    }

    return true;
  }

  /** Model hook to decrypt model data if auto decipher is turned on in the
    * model behavior configuration. Only primary model data are decrypted. */
  public function afterFind(Model $model, $result, $primary = false) {
    if (!$result || !isset($this->config[$model->name]['cipher']))
      return $result;

    if ($primary && $this->config[$model->name]['autoDecrypt']) {
      // check for single of multiple model
      $keys = array_keys($result);
      if (!is_numeric($keys[0])) {
        $this->decrypt($model, $result);
      } else {
        foreach($keys as $index) {
          $this->decrypt($model, $result[$index]);
        }
      }
    }
    return $result;
  }

  /** Decrypt model value
    @param model Current model
    @param data Current model data. If null, the Model::data is used
    @return Deciphered model data */
  public function decrypt(&$model, $data = null) {
    $this->log(print_r($data, true));
    if ($data === null)
      $data = $model->data;
    if (isset($this->config[$model->name])) {
      if (!is_array($this->config[$model->name]['cipher'])) {
        $cipher = array($this->config[$model->name]['cipher']);
      } else {
        $cipher = $this->config[$model->name]['cipher'];
      }

      $prefix = $this->config[$model->name]['prefix'];
      $prefixLen = strlen($prefix);
      foreach ($cipher as $column) {
        if (!empty($data[$model->name][$column]) &&
          substr($data[$model->name][$column], 0, $prefixLen) == $prefix) {
          $decrypt = $this->_decryptValue($data[$model->name][$column], $this->config[$model->name]);
          if ($decrypt) {
            $data[$model->name][$column] = $decrypt;
          } else {
            $this->log(__METHOD__." Could not decrypt {$model->name}::$column: '{$data[$model->name][$column]}'");
          }
        }
      }
    }
    return $data;
  }

  /** Create salt for cipher's envelope. The salt is an random string which
   * depends on the random generator, the value, the key and on the previous
   * generated character.
    @param value Value to cipher
    @param key Key for encrpytion.
    @param len Length of resulting salt. Default is 4
    @return Randomly generated salt of the given lenth */
  public function _generateSalt($value, $key = '9nHPrYcxmvTliA', $len = 4) {
    srand(microtime(true)*1000);
    $salt = '';
    $lenKey = strlen($key);
    $lenValue = strlen($value);
    $old = rand(0, 255);
    for($i = 0; $i < $len; $i++) {
      $n = ord($key[$i % $lenKey]);
      for ($j = 0; $j < $n; $j++) {
        $toss = rand(0, 255);
      }
      $toss ^= $n;
      $toss ^= ord($value[$i % $lenValue]);
      $toss ^= $old;
      $salt .= chr($toss);
      $old = $toss;
    }
    return $salt;
  }

  /** Packs a value with a surrounding salt value. Additionaly the resulting
   * envelope could be aligned
    @param value Value to envelope
    @param salt Salt which builds the prefix and suffix of the envelope
    @param padding Alignment size. Default is 4
    @return Envelope with salt
    @see _unpackValue() */
  public function _packValue($value, $salt, $padding = 4) {
    $l = strlen($value) + 2 * strlen($salt);
    $lp = $l % $padding;
    $pad = '';
    if ($lp) {
      $pad = str_repeat(chr(0), $lp-1).chr($lp);
    }
    return $salt.$value.$pad.$salt;
  }

  /** Unpacks an envelope and returns the packed value
    @param envelope
    @return Value or false on an error
    @see _packValue() */
  public function _unpackValue($envelope, $saltLen) {
    $l = strlen($envelope);
    if ($l < 2*$saltLen) {
      $this->log(__METHOD__." Value for unpacking is to short");
      return false;
    }
    $salt = substr($envelope, 0, $saltLen);
    if ($salt != substr($envelope, $l - $saltLen, $saltLen)) {
      $this->log(__METHOD__." Enclosed salt missmatch: '$salt' != '".substr($envelope, $l - $saltLen, $saltLen)."' $l");
      return false;
    }
    $pad = ord(substr($envelope, $l - $saltLen -1, 1));
    if ($pad > 32) {
      $pad = 0;
    }
    $value = substr($envelope, $saltLen, $l - (2 * $saltLen) - $pad);
    return $value;
  }

  /** Encrpytes a value using the blowfish cipher. As key the Security.salt
    * value is used
    @param value Value to cipher
    @return Return of the chiphered value in base64 encoding. To distinguish
    ciphed value, the ciphed value has a prefix of '$E$' i
    @see _decryptValue(), _packValue(), _generateSalt() */
  public function _encryptValue($value, $config) {
    extract($config);
    $bf = new Crypt_Blowfish($key);

    $enclose = $this->_packValue($value, $this->_generateSalt($value, $key, $saltLen), $padding);
    $encrypted = $bf->encrypt($enclose);
    if (!is_string($encrypted)) {
      $this->log($encrypted->getMessage());
      return false;
    }
    return $prefix.base64_encode($encrypted);
  }

  /** Decrpyted the given base64 string using the blowfish cipher
    @param base64Value Base 64 encoded string.
    @see _encryptValue(), _unpackValue() */
  public function _decryptValue($base64Value, $config) {
    extract($config);
    $prefixLen = strlen($prefix);
    if (substr($base64Value, 0, $prefixLen) != $prefix) {
      $this->log(__METHOD__." Security prefix is missing: '$base64Value'");
      return false;
    }
    $encrypted  = base64_decode(substr($base64Value, $prefixLen));
    if ($encrypted === false) {
      $this->log(__METHOD__." Could not decode base64 value '$base64Value'");
      return false;
    }
    $bf = new Crypt_Blowfish($key);

    $envelope = trim($bf->decrypt($encrypted), chr(0));
    $value = $this->_unpackValue($envelope, $saltLen);
    if ($value === false) {
      $this->log(__METHOD__." Could not unpack value from '$envelope'");
      return false;
    }

    if (!is_string($value)) {
      $this->log($value->getMessage());
      return false;
    }
    return $value;
  }

}
?>
