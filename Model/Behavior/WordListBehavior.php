<?php
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2012, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

class WordListBehavior extends ModelBehavior 
{
  var $config = array();

  function setup(&$Model, $config = array()) {
    $default = array('field' => 'name');
    $this->config[$Model->alias] = am($config, $default);
  }
  
  function findAllByField(&$Model, $values, $createMissing = true) {
    $config = $this->config[$Model->alias];
    $field = $config['field'];
    $alias = $Model->alias;

    $data = $Model->find('all', array('conditions' => array($field => $values)));
    if (!$createMissing) {
       return $data;
    }
    $found = Set::extract("/{$alias}/{$field}", $data);
    $missing = array_diff($values, $found);
    foreach ($missing as $missingfield) {
      $new = array($alias => array($field => $missingfield));
      if ($Model->save($new)) {
        $new = $Model->findById($Model->getInsertID());
        Logger::debug("Create new {$alias} with ${field} '$missingfield'");
        $data[] = $new;
      } else {
        Logger::debug("Could not create new model {$alias} with ${field} '$missingfield'");
      }
    }
    return $data;
  }
  
  /** 
   * Split input into words sparated by comma
   * 
   * @param type $input Text input
   * @param type $withRemovals Include words with leading minus
   * @return type Array of words
   */
  function splitWords(&$Model, $input) {
    $words = split(',', $input);
    $names = array();
    foreach ($words as $name) {
      $name = trim($name);
      if (!$name) {
        continue;
      }
      $names[] = $name;
    }
    return $names;
  }
  
  /**
   * Remove words which are negated
   * 
   * @param type $Model Current model
   * @param type $words Array of words
   * @return array Remaining non negated words 
   */
  function removeNegatedWords(&$Model, $words) {
    $result = array();
    foreach ((array) $words as $word) {
      if (!$word) {
        continue;
      }
      if ($word[0] != '-') {
        $result[] = $word;
      }
    }
    return $result;
  }
  
  /**
   * Collects all negated words and returns them. 
   * 
   * @param type $Model Current model
   * @param type $words Array of words
   * @return array Array of negated words 
   */
  function getNegatedWords(&$Model, $words) {
    $result = array();
    foreach ((array) $words as $word) {
      if (!$word) {
        continue;
      }
      if ($word[0] == '-') {
        $word = trim(substr($word, 1));
        if ($word) {
          $result[] = $word;
        }
      }
    }
    return $result;
  }
  
  /**
   * Normalize worlds and remove negation sign of words.
   * 
   * @param type $Model Current Model
   * @param type $words Array of words
   * @return array 
   */
  function normalizeWords(&$Model, $words) {
    $result = array();
    foreach ((array) $words as $word) {
      if (!$word) {
        continue;
      }
      if ($word[0] == '-') {
        $word = trim(substr($word, 1));
        if ($word) {
          $result[] = $word;
        }
      } else {
        $result[] = $word;
      }
    }
    return $result;
  }
}
?>
