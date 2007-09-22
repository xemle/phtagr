<?php

define("EXIF_BYTE",       0x01);
define("EXIF_ASCII",      0x02);
define("EXIF_SHORT",      0x03);
define("EXIF_LONG",       0x04);
define("EXIF_RATIONAL",   0x05);
define("EXIF_UNDEFINED",  0x07);
define("EXIF_SLONG",      0x09);
define("EXIF_SRATIONAL",  0x0a);

// Exif Attribute Table defined in Exif 2.2
// id - Field id, given in decimal
// id['name'] - Field name (mandatory)
// id['type'] - Attribute type (mandatory)
// id['type2'] - Alternative attribute type (optional)
// id['count'] - Restricted Count if given, otherwise any (optional)
$ExifAttributeTable=array(

  // 4.6.4 TIFF Rev. 6.0 Attribute Information
  // A. Tags relating to image data structure 
  256 => array('name' => 'ImageWidth', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG, 'count' => 1),
  257 => array('name' => 'ImageLength', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG, 'count' => 1),
  258 => array('name' => 'BitsPerSample', 'type' => EXIF_SHORT, 'count' => 3),
  259 => array('name' => 'Compression', 'type' => EXIF_SHORT, 'count' => 1),
  262 => array('name' => 'PhotometricInterpretation', 'type' => EXIF_SHORT, 'count' => 1),
  274 => array('name' => 'Orientation', 'type' => EXIF_SHORT, 'count' => 1),
  277 => array('name' => 'SamplesPerPixel', 'type' => EXIF_SHORT, 'count' => 1),
  284 => array('name' => 'PlanarConfiguration', 'type' => EXIF_SHORT, 'count' => 1),
  530 => array('name' => 'YCbCrSubSampling', 'type' => EXIF_SHORT, 'count' => 2),
  531 => array('name' => 'YCbCrPositioning', 'type' => EXIF_SHORT, 'count' => 1),
  282 => array('name' => 'XResolution', 'type' => EXIF_RATIONAL, 'count' => 1),
  283 => array('name' => 'YResolution', 'type' => EXIF_RATIONAL, 'count' => 1),
  296 => array('name' => 'ResolutionUnit', 'type' => EXIF_SHORT, 'count' => 1),
  // B. Tags relating to recording offset 
  273 => array('name' => 'StripOffsets', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG),
  278 => array('name' => 'RowsPerStrip', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG, 'count' => 1),
  279 => array('name' => 'StripByteCounts', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG),
  513 => array('name' => 'JPEGInterchangeFormat', 'type' => EXIF_LONG, 'count' => 1),
  514 => array('name' => 'JPEGInterchangeFormatLength', 'type' => EXIF_LONG, 'count' => 1),
  // C. Tags relating to image data characteristics 
  301 => array('name' => 'TransferFunction', 'type' => EXIF_SHORT, 'count' => 768),
  318 => array('name' => 'WhitePoint', 'type' => EXIF_RATIONAL, 'count' => 2),
  319 => array('name' => 'PrimaryChromaticities', 'type' => EXIF_RATIONAL, 'count' => 6),
  529 => array('name' => 'YCbCrCoefficients', 'type' => EXIF_RATIONAL, 'count' => 3),
  532 => array('name' => 'ReferenceBlackWhite', 'type' => EXIF_RATIONAL, 'count' => 6),
  // D. Other tags 
  306 => array('name' => 'DateTime', 'type' => EXIF_ASCII, 'count' => 20),
  270 => array('name' => 'ImageDescription', 'type' => EXIF_ASCII),
  271 => array('name' => 'Make', 'type' => EXIF_ASCII),
  272 => array('name' => 'Model', 'type' => EXIF_ASCII),
  305 => array('name' => 'Software', 'type' => EXIF_ASCII),
  315 => array('name' => 'Artist', 'type' => EXIF_ASCII),
  33432 => array('name' => 'Copyright', 'type' => EXIF_ASCII),
          
  // 4.6.5 Exif IFD Attrribute Information
  // A. Tags Relating to Version 
  36864 => array('name' => 'ExifVersion', 'type' => EXIF_UNDEFINED, 'count' => 4),
  40960 => array('name' => 'FlashpixVersion', 'type' => EXIF_UNDEFINED, 'count' => 4),
  // B. Tag Relating to Image Data Characteristics 
  40961 => array('name' => 'ColorSpace', 'type' => EXIF_SHORT, 'count' => 1),
  // C. Tags Relating to Image Configuration
  37121 => array('name' => 'ComponentsConfiguration', 'type' => EXIF_UNDEFINED, 'count' => 4),
  37122 => array('name' => 'CompressedBitsPerPixel', 'type' => EXIF_RATIONAL, 'count' => 1),
  40962 => array('name' => 'PixelXDimension', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG, 'count' => 1),
  40963 => array('name' => 'PixelYDimension', 'type' => EXIF_SHORT, 'type2' => EXIF_LONG, 'count' => 1),
  // D. Tags Relating to User Information 
  37500 => array('name' => 'MakerNote', 'type' => EXIF_UNDEFINED),
  37510 => array('name' => 'UserComment', 'type' => EXIF_UNDEFINED),
  // E. Tag Relating to Related File Information 
  40964 => array('name' => 'RelatedSoundFile', 'type' => EXIF_ASCII, 'count' => 13),
  // F. Tags Relating to Date and Time 
  36867 => array('name' => 'DateTimeOriginal', 'type' => EXIF_ASCII, 'count' => 20),
  36868 => array('name' => 'DateTimeDigitized', 'type' => EXIF_ASCII, 'count' => 20),
  37520 => array('name' => 'SubSecTime', 'type' => EXIF_ASCII),
  37521 => array('name' => 'SubSecTimeOriginal', 'type' => EXIF_ASCII),
  37522 => array('name' => 'SubSecTimeDigitized', 'type' => EXIF_ASCII),
  // G. Tags Relating to Picture-Taking Conditions 
  33434 => array('name' => 'ExposureTime', 'type' => EXIF_RATIONAL, 'count' => 1),
  33437 => array('name' => 'FNumber', 'type' => EXIF_RATIONAL, 'count' => 1),
  34850 => array('name' => 'ExposureProgram', 'type' => EXIF_SHORT, 'count' => 1),
  34852 => array('name' => 'SpectralSensitivity', 'type' => EXIF_ASCII),
  34855 => array('name' => 'ISOSpeedRatings', 'type' => EXIF_SHORT),
  34856 => array('name' => 'OECF', 'type' => EXIF_UNDEFINED),
  37377 => array('name' => 'ShutterSpeedValue', 'type' => SRATIONAL, 'count' => 1),
  37378 => array('name' => 'ApertureValue', 'type' => EXIF_RATIONAL, 'count' => 1),
  37379 => array('name' => 'BrightnessValue', 'type' => SRATIONAL, 'count' => 1),
  37380 => array('name' => 'ExposureBiasValue', 'type' => SRATIONAL, 'count' => 1),
  37381 => array('name' => 'MaxApertureValue', 'type' => EXIF_RATIONAL, 'count' => 1),
  37382 => array('name' => 'SubjectDistance', 'type' => EXIF_RATIONAL, 'count' => 1),
  37383 => array('name' => 'MeteringMode', 'type' => EXIF_SHORT, 'count' => 1),
  37384 => array('name' => 'LightSource', 'type' => EXIF_SHORT, 'count' => 1),
  37385 => array('name' => 'Flash', 'type' => EXIF_SHORT, 'count' => 1),
  37386 => array('name' => 'FocalLength', 'type' => EXIF_RATIONAL, 'count' => 1),
  37396 => array('name' => 'SubjectArea', 'type' => EXIF_SHORT),
  41483 => array('name' => 'FlashEnergy', 'type' => EXIF_RATIONAL, 'count' => 1),
  41484 => array('name' => 'SpatialFrequencyResponse', 'type' => EXIF_UNDEFINED),
  41486 => array('name' => 'FocalPlaneXResolution', 'type' => EXIF_RATIONAL, 'count' => 1),
  41487 => array('name' => 'FocalPlaneYResolution', 'type' => EXIF_RATIONAL, 'count' => 1),
  41488 => array('name' => 'FocalPlaneResolutionUnit', 'type' => EXIF_SHORT, 'count' => 1),
  41492 => array('name' => 'SubjectLocation', 'type' => EXIF_SHORT, 'count' => 2),
  41493 => array('name' => 'ExposureIndex', 'type' => EXIF_RATIONAL, 'count' => 1),
  41495 => array('name' => 'SensingMethod', 'type' => EXIF_SHORT, 'count' => 1),
  41728 => array('name' => 'FileSource', 'type' => EXIF_UNDEFINED, 'count' => 1),
  41729 => array('name' => 'SceneType', 'type' => EXIF_UNDEFINED, 'count' => 1),
  41730 => array('name' => 'CFAPattern', 'type' => EXIF_UNDEFINED),
  41985 => array('name' => 'CustomRendered', 'type' => EXIF_SHORT, 'count' => 1),
  41986 => array('name' => 'ExposureMode', 'type' => EXIF_SHORT, 'count' => 1),
  41987 => array('name' => 'WhiteBalance', 'type' => EXIF_SHORT, 'count' => 1),
  41988 => array('name' => 'DigitalZoomRatio', 'type' => EXIF_RATIONAL, 'count' => 1),
  41989 => array('name' => 'FocalLengthIn35mmFilm', 'type' => EXIF_SHORT, 'count' => 1),
  41990 => array('name' => 'SceneCaptureType', 'type' => EXIF_SHORT, 'count' => 1),
  41991 => array('name' => 'GainControl', 'type' => EXIF_RATIONAL, 'count' => 1),
  41992 => array('name' => 'Contrast', 'type' => EXIF_SHORT, 'count' => 1),
  41993 => array('name' => 'Saturation', 'type' => EXIF_SHORT, 'count' => 1),
  41994 => array('name' => 'Sharpness', 'type' => EXIF_SHORT, 'count' => 1),
  41995 => array('name' => 'DeviceSettingDescription', 'type' => EXIF_UNDEFINED),
  41996 => array('name' => 'SubjectDistanceRange', 'type' => EXIF_SHORT, 'count' => 1),
  // H. Other Tags
  42016 => array('name' => 'ImageUniqueID', 'type' => EXIF_ASCII, 'count' => 33),
          
  // 4.6.6 GPS Attribute Information
  // A. Tags Relating to GPS
  0 => array('name' => 'GPSVersionID', 'type' => EXIF_BYTE, 'count' => 4),
  1 => array('name' => 'GPSLatitudeRef', 'type' => EXIF_ASCII, 'count' => 2),
  2 => array('name' => 'GPSLatitude', 'type' => EXIF_RATIONAL, 'count' => 3),
  3 => array('name' => 'GPSLongitudeRef', 'type' => EXIF_ASCII, 'count' => 2),
  4 => array('name' => 'GPSLongitude', 'type' => EXIF_RATIONAL, 'count' => 3),
  5 => array('name' => 'GPSAltitudeRef', 'type' => EXIF_BYTE, 'count' => 1),
  6 => array('name' => 'GPSAltitude', 'type' => EXIF_RATIONAL, 'count' => 1),
  7 => array('name' => 'GPSTimeStamp', 'type' => EXIF_RATIONAL, 'count' => 3),
  8 => array('name' => 'GPSSatellites', 'type' => EXIF_ASCII),
  9 => array('name' => 'GPSStatus', 'type' => EXIF_ASCII, 'count' => 2),
  10 => array('name' => 'GPSMeasureMode', 'type' => EXIF_ASCII, 'count' => 2),
  11 => array('name' => 'GPSDOP', 'type' => EXIF_RATIONAL, 'count' => 1),
  12 => array('name' => 'GPSSpeedRef', 'type' => EXIF_ASCII, 'count' => 2),
  13 => array('name' => 'GPSSpeed', 'type' => EXIF_RATIONAL, 'count' => 1),
  14 => array('name' => 'GPSTrackRef', 'type' => EXIF_ASCII, 'count' => 2),
  15 => array('name' => 'GPSTrack', 'type' => EXIF_RATIONAL, 'count' => 1),
  16 => array('name' => 'GPSImgDirectionRef', 'type' => EXIF_ASCII, 'count' => 2),
  17 => array('name' => 'GPSImgDirection', 'type' => EXIF_RATIONAL, 'count' => 1),
  18 => array('name' => 'GPSMapDatum', 'type' => EXIF_ASCII),
  19 => array('name' => 'GPSDestLatitudeRef', 'type' => EXIF_ASCII, 'count' => 2),
  20 => array('name' => 'GPSDestLatitude', 'type' => EXIF_RATIONAL, 'count' => 3),
  21 => array('name' => 'GPSDestLongitudeRef', 'type' => EXIF_ASCII, 'count' => 2),
  22 => array('name' => 'GPSDestLongitude', 'type' => EXIF_RATIONAL, 'count' => 3),
  23 => array('name' => 'GPSDestBearingRef', 'type' => EXIF_ASCII, 'count' => 2),
  24 => array('name' => 'GPSDestBearing', 'type' => EXIF_RATIONAL, 'count' => 1),
  25 => array('name' => 'GPSDestDistanceRef', 'type' => EXIF_ASCII, 'count' => 2),
  26 => array('name' => 'GPSDestDistance', 'type' => EXIF_RATIONAL, 'count' => 1),
  27 => array('name' => 'GPSProcessingMethod', 'type' => EXIF_UNDEFINED),
  28 => array('name' => 'GPSAreaInformation', 'type' => EXIF_UNDEFINED),
  29 => array('name' => 'GPSDateStamp', 'type' => EXIF_ASCII, 'count' => 11),
  30 => array('name' => 'GPSDifferential', 'type' => EXIF_SHORT, 'count' => 1)
);

$InteropAttributeTable=array(
  1 => array('name' => 'InteroperabilityIndex', 'type' => EXIF_ASCII, 'count' => 4),
  2 => array('name' => 'InteroperabilityVersion', 'type' => EXIF_UNDEFINED, 'count' => 4)
);

?>
