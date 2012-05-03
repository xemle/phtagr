<?php
  if (empty($_SERVER['PATH_INFO']) && !empty($_SERVER['ORIG_PATH_INFO']))
  {
    $this->log("Set PATH_INFO to ".$_SERVER['ORIG_PATH_INFO'], LOG_DEBUG);
    $_SERVER['PATH_INFO']=$_SERVER['ORIG_PATH_INFO'];
  }

  vendor("webdav/WebdavServer");
  $webdav = new WebdavServer();
  $webdav->set_scriptname('webdav');
  $webdav->set_base("/var/www/cake/app/webroot");
  $this->log("Before Request", LOG_DEBUG);
  $webdav->ServeRequest();
  $this->log("After Request", LOG_DEBUG);
?>
