<?php

// ACL constants
define("ACL_DOWNLOAD", 0x80);
define("ACL_DOWNLOAD_MASK", 0x80);
define("ACL_FULLSIZE", 0x40);
define("ACL_FULLSIZE_MASK", 0xc0);
define("ACL_HIGHSOLUTION", 0x20);
define("ACL_HIGHSOLUTION_MASK", 0xe0);
define("ACL_PREVIEW", 0x10);
define("ACL_PREVIEW_MASK", 0xf0);
define("ACL_METADATA", 0x02);
define("ACL_EDIT", 0x01);
define("ACL_EDIT_MASK", 0x01);

define("ACL_GROUP", 0x00);
define("ACL_OTHER", 0x01);
define("ACL_ALL", 0x02);

define("LOCATION_UNDEFINED", 0x00);
define("LOCATION_CITY", 0x01);
define("LOCATION_SUBLOCATION", 0x02);
define("LOCATION_STATE", 0x03);
define("LOCATION_COUNTRY", 0x04);

?>
