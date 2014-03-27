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

  /**
   * If $scaleMode is set to 'width' the watermark is scaled to fit the width
   * of the image. Analog to $scaleMode 'height'.
   *
   * If $scaleMode is set to 'bestFit' the watermark ist scaled that it fits
   * into the image without cutting it.
   *
   * The watermark is not scaled if $scaleMode is set to 'none'.
   *
   * On $scaleMode 'inner' the longer watermark side is scaled to the shorter
   * image side. This ensures that the watermark has equal sizes for different
   * image orientations of portait and landscape.
   *
   * @param int $imageWidth
   * @param int $imageHeight
   * @param int $watermarkWidth
   * @param int $watermarkHeight
   * @param string $scaleMode
   * @return float Scaling factor
   */
  private function getScaleFactor($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $scaleMode) {
    $scaleFactor = 1.0;
    if ($scaleMode == 'width') {
      $scaleFactor = $imageWidth / $watermarkWidth;
    } else if ($scaleMode == 'height') {
      $scaleFactor = $imageHeight / $watermarkHeight;
    } else if ($scaleMode == 'bestFit') {
      $scaleWidth = $imageWidth / $watermarkWidth;
      $scaleHeight = $imageHeight / $watermarkHeight;

      $scaleFactor = min($scaleWidth, $scaleHeight);
    } else if ($scaleMode == 'none') {
      $scaleFactor = 1.0;
    } else if ($scaleMode == 'inner') {
      $imageBoundingBox = min($imageWidth, $imageHeight);
      $watermarkBoundingBox = max($watermarkWidth, $watermarkHeight);

      $scaleFactor = $imageBoundingBox / $watermarkBoundingBox;
    } else {
      CakeLog::warning("Invalid scaleMode $scaleMode. Do not scale watermark");
    }

    return $scaleFactor;
  }

  /**
   * Scale an image from its source size to given target size
   *
   * @param resource $image Resource of image source
   * @param int $imageWidth
   * @param int $imageHeight
   * @param int $targetWidth
   * @param int $targetHeight
   * @return resouce Resource of scaled image
   */
  private function scaleImage($image, $imageWidth, $imageHeight, $targetWidth ,$targetHeight) {
    $scaledImage = imagecreatetruecolor($targetWidth, $targetHeight);
    $white = imagecolorallocate($scaledImage, 255, 255, 255);
    imagecolortransparent($scaledImage, $white);
    imagealphablending($scaledImage, false);
    imagecopyresized($scaledImage, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);

    return $scaledImage;
  }

  /**
   * Scale watermark image according to size of image, watermark and scale mode.
   *
   * @param resource $image
   * @param resource $watermark
   * @param string $scaleMode Scale mode of width, height, bestFit, none, inner.
   * Default is inner. See function getScaleFactor()
   * @return mixed Watermark resource on success. False otherwise
   */
  private function scaleWatermark($image, $watermark, $scaleMode) {
    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);
    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);

    if ($imageWidth * $imageHeight == 0) {
      $this->errors[] = "Invalid image size of {$imageWidth}x$imageHeight";
      return false;
    }
    if ($watermarkWidth * $watermarkHeight == 0) {
      $this->errors[] = "Invalid watermark size of {$watermarkWidth}x$watermarkHeight";
      return false;
    }

    // Set default option to inner if not set
    if (!$scaleMode) {
      $scaleMode = 'inner';
    }
    $scaleFactor = $this->getScaleFactor($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $scaleMode);
    if ($scaleFactor == 1.0) {
      return $watermark;
    }

    return $this->scaleImage($watermark, $watermarkWidth, $watermarkHeight, (int) ($scaleFactor * $watermarkWidth), (int) ($scaleFactor * $watermarkHeight));
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
   * @param string $scaleMode Position mode. See function scaleWatermark()
   * @param string $position See function positionWatermark()
   */
  private function applyWatermark($image, $watermark, $scaleMode, $position) {
    $scaledWatermark = $this->scaleWatermark($image, $watermark, $scaleMode);
    if (!$scaledWatermark) {
      return false;
    }

    $imageWidth = imagesx($image);
    $imageHeight = imagesy($image);

    $watermarkWidth = imagesx($scaledWatermark);
    $watermarkHeight = imagesy($scaledWatermark);

    list($watermarkX, $watermarkY) = $this->positionWatermark($imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight, $position);
    imagecopy($image, $scaledWatermark, $watermarkX, $watermarkY, 0, 0, $watermarkWidth, $watermarkHeight);

    // Free resource
    if ($watermark != $scaledWatermark) {
      imagedestroy($scaledWatermark);
    }
    return true;
  }

  /**
   * Add an watermark to given image source file
   *
   * @param string $src Filename of image source file
   * @param string $watermarkSrc Filename of watermark image
   * @param string $scaleMode See scaleWatermark() for different scale modes
   * @param string $position See positionWatermark() with cardinal direction
   * of 'n' for north, 'e' for east, 's' for south, and 'w' for west. Use 'se'
   * to place the watermark to south east which is right bottom. Use empty string
   * for center. Default is center
   * @param string $dst Optional destination file. If obmitted the watermark is
   * applied to $src
   * @return boolean Return true on success. If false the errors are listed
   * in WatermarkCreator::errors array.
   */
  public function create($src, $watermarkSrc, $scaleMode = 'inner', $position = '', $dst = null) {
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
    if (!$this->applyWatermark($image, $watermark, $scaleMode, $position)) {
      return false;
    }

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