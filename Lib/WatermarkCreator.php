<?php
/**
 * PHP versions 5
 *
 * phTagr : Organize, Browse, and Share Your Photos.
 * Copyright 2006-2014, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.3-dev
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */


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

  private function scaleImage($image, $width, $height, $scale) {
    $scaledWidth = (int) ($scale * $width);
    $scaledHeight = (int) ($scale * $height);

    $scaledImage = imagecreatetruecolor($scaledWidth, $scaledHeight);
    $white = imagecolorallocate($scaledImage, 255, 255, 255);
    imagecolortransparent($scaledImage, $white);
    imagealphablending($scaledImage, false);
    imagecopyresized($scaledImage, $image, 0, 0, 0, 0, $scaledWidth, $scaledHeight, $width, $height);

    return $scaledImage;
  }

  /**
   * Position the watermark and return watermark offset.
   *
   * @param int $imageWidth
   * @param int $imageHeight
   * @param int $watermarkWidth
   * @param int $watermarkHeight
   * @param string $position Position. 'n' for north, 'e' for east, 's' for south,
   * and 'w' for west. To place watermark in left bottom use south-east with 'se'.
   * Default position is centered.
   * @return array of offsetX and offsetY
   */
  private function positionWatermark($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $position = '') {
    $position = strtolower($position);
    $offsetX = ($imageWidth - $watermarkWidth) / 2;
    $offsetY = ($imageHeight - $watermarkHeight) / 2;

    // Position north and south
    if (strpos($position, 'n') !== false) {
      $offsetY = 0;
    } else if (strpos($position, 's') !== false) {
      $offsetY = $imageHeight - $watermarkHeight;
    }
    // Position west and east
    if (strpos($position, 'w') !== false) {
      $offsetX = 0;
    } else if (strpos($position, 'e') !== false) {
      $offsetX = $imageWidth - $watermarkWidth;
    }

    return array($offsetX, $offsetY);
  }

  /**
   * Apply watermark to image buffer. The watermark will be scaled into the
   * image that the outer watermark bounding box is equal to the inner image
   * bounding box. The inner bounding box is the box of the shorter side and
   * the outer bounding box is the longer side.
   *
   * @param resource $image Buffer of image
   * @param resource $watermark Buffer of watermark
   * @param string $position See function _positionWatermark()
   */
  private function applyWatermark($image, $watermark, $position) {
    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $imgInnerBoundingBox = min($imageWidth, $imageHeight);

    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);
    $watermarkOuterBoundingBox = max($watermarkWidth, $watermarkHeight);

    $scale = $imgInnerBoundingBox / $watermarkOuterBoundingBox;
    if ($scale != 1) {
      $watermark = $this->scaleImage($watermark, $watermarkWidth, $watermarkHeight, $scale);
      $watermarkWidth = (int) ($scale * $watermarkWidth);
      $watermarkHeight = (int) ($scale * $watermarkHeight);
    }

    list($watermarkX, $watermarkY) = $this->positionWatermark($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $position);
    imagecopy($image, $watermark, $watermarkX, $watermarkY, 0, 0, $watermarkWidth, $watermarkHeight);

    // Free scaled image buffer
    if ($scale != 1) {
      imagedestroy($watermark);
    }
  }

  /**
   * Add an watermark to given image source file
   *
   * @param string $src Filename of image source file
   * @param string $watermarkSrc Filename of watermark image
   * @param string $position See _positionWatermark() with cardinal direction
   * of 'n' for north, 'e' for east, 's' for south, and 'w' for west. Use 'se'
   * to place the watermark to south east which is right bottom. Use empty string
   * for center. Default is center
   * @param string $dst Optional destination file. If obmitted the watermark is
   * applied to $src
   * @return boolean Return true on success. If false the errors are listed
   * in WatermarkCreator::errors array.
   */
  public function create($src, $watermarkSrc, $position = '', $dst = null) {
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
    $this->applyWatermark($image, $watermark, $position);

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