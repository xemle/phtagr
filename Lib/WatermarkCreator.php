<?php

/**
 * Add a watermark to an image via GD library
 *
 * Usage:
 *
 * App::uses('WatermarkCreator', 'Lib'); // for CakePHP
 * $watermark = new Watermark();
 * $watermark->create($src, $watermark);
 *
 * If any error occurs you can access the error array via
 * $watermark->errors;
 */
class WatermarkCreator {

  var $errors = array();
  var $maxSize = 1920;

  private function getExtension($filename) {
    return strtolower(substr($filename, strrpos($filename, '.') + 1));
  }
  private function createImage($filename) {
    $ext = $this->getExtension($filename);
    if ($ext == 'jpeg' || $ext == 'jpg' || $ext == 'thm') {
      return imagecreatefromjpeg($filename);
    } else if ($ext == 'png') {
      return imagecreatefrompng($filename);
    } else if ($ext == 'webp') {
      return imagecreatefromwebp($filename);
    } else if ($ext == 'gif') {
      return imagecreatefromgif($filename);
    }
    $this->errors[] = "Unknown extension $ext for input image $filename";
    return false;
  }

  private function saveImage($image, $filename, $quality) {
    if (!is_writable(dirname($filename))) {
      $this->errors[] = "Directory " . dirname($filename) . " of destination file $filename is not writeable";
      return false;
    }
    $ext = $this->getExtension($filename);
    if ($ext == 'jpeg' || $ext == 'jpg' || $ext == 'thm') {
      return imagejpeg($image, $filename, $quality);
    } else if ($ext == 'png') {
      return imagepng($image, $filename);
    } else if ($ext == 'webp') {
      return imagewebp($image, $filename);
    } else if ($ext == 'gif') {
      return imagegif($image, $filename);
    }
    $this->errors[] = "Unknown extension $ext for output image $filename";
    return false;
  }

  private function _applyWatermark($image, $watermark) {
    $imgWidth = imagesx($image);
    $imgHeight = imagesy($image);

    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);
    if ($imgWidth > $this->maxSize || $imgHeight > $this->maxSize) {
      $watermarkX = ($imgWidth - $watermarkWidth) / 2;
      $watermarkY = ($imgHeight - $watermarkHeight) / 2;
      imagecopy($image, $watermark, $watermarkX, $watermarkY, 0, 0, $watermarkWidth, $watermarkHeight);
    } else {
      // resize to suit smaller thumbnails
      $scale = $imgWidth / $this->maxSize;
      $scaledWidth = (int) ($scale * $watermarkWidth);
      $scaledHeight = (int) ($scale * $watermarkHeight);

      $scaledWatermark = imagecreatetruecolor($scaledWidth, $scaledHeight);
      $white = imagecolorallocate($scaledWatermark, 255, 255, 255);
      imagecolortransparent($scaledWatermark, $white);
      imagealphablending($scaledWatermark, false);
      imagecopyresized($scaledWatermark, $watermark, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $watermarkWidth, $watermarkHeight);

      $watermarkX = ($imgWidth - $scaledWidth) / 2;
      $watermarkY = ($imgHeight - $scaledHeight) / 2;

      imagecopy($image, $scaledWatermark, $watermarkX, $watermarkY, 0, 0, $scaledWidth, $scaledHeight);
    }
  }

  /**
   * Add an watermark to given image source file
   *
   * @param string $src Filename of image source file
   * @param string $watermarkSrc Filename of watermark image
   * @param string $dst Optional destination file. If obmitted the watermark is
   * applied to $src
   * @return boolean Return true on success. If false the errors are listed
   * in WatermarkCreator::errors array.
   */
  public function create($src, $watermarkSrc, $dst = null) {
    // Reset errors
    $this->errors = array();

    // Check preconditions
    if (!function_exists('imagecreatefromjpeg')) {
      $this->errors[] = "GD extension is missing";
      return false;
    }
    if (!is_readable($src)) {
      $this->errors[] = "Source file $src is not readable";
      return false;
    }
    if (!is_readable($watermarkSrc)) {
      $this->errors[] = "Watermark file $watermarkSrc is not readable";
      return false;
    }

    // Load image into GD resources
    $image = $this->createImage($src);
    if (!$image) {
      return false;
    }
    $watermark = $this->createImage($watermarkSrc);
    if (!$watermark) {
      imagedestroy($image);
      return false;
    }
    
    // Add watermark image
    $this->_applyWatermark($image, $watermark);

    // Save final watermark image
    if ($dst === null) {
      $dst = $src;
    }
    $result = $this->saveImage($image, $dst, 95);

    imagedestroy($image);
    imagedestroy($watermark);
    return $result;
  }

}