<?php
  echo __("Hello %s!", $user['User']['username']);
  echo __("\n");
  echo __("Please confirm your account creation")."\n";
  echo "\n";
  echo Router::url('/users/confirm/'.$key, true)."\n";
  echo "\n";
  echo __("Or visit %s and enter the confirmation key:", Router::url('/users/confirm', true))."\n";
  echo "\n";
  echo $key."\n";
  echo "\n";
  echo __("to finalize your account creation")."\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent");
?>
