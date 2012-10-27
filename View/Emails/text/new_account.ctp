<?php
  echo __("Welcome %s!", $user['User']['username'])."\n";
  echo "\n";
  echo __("Your phTagr account is now activated for %s", Router::url('/', true))."\n";
  echo "\n";
  echo __("Login at %s with your new account and see %s for first steps.", Router::url('/users/login', true), "http://trac.phtagr.org/wiki/FirstSteps")."\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent");
?>
