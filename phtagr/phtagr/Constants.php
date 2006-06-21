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

define("ACL_GROUP", 0);
define("ACL_OTHER", 1);
define("ACL_ALL", 2);

?>
