<?php
  $this->response->type('application/xml');

  echo "<?xml version='1.0' encoding='UTF-8'?>"."\n";
  echo $content_for_layout;
