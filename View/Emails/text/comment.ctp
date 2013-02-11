<?php 
  echo __("Hi %s", $user['User']['username']);
  echo "\n";
  echo __("New comment on your media %s: %s was submitted", '#'.$data['Media']['id'], $data['Media']['name']);
  echo "\n";
  echo "\n";
  echo __("Author: %s", $data['Comment']['name'])."\n";
  echo __("Email: %s", $data['Comment']['email'])."\n";
  echo __("URL: %s", $data['Comment']['url'])."\n";
  echo __("Date: %s", $data['Comment']['date'])."\n";
  echo "\n";
  echo $data['Comment']['text']."\n";
  echo "\n";
  echo __("You can see the comments on this media here: %s", Router::url('/images/view/'.$data['Media']['id'], true))."\n";
  echo "\n";
  echo __("Delete it: %s", Router::url('/comments/delete/'.$data['Comment']['id'], true))."\n";
  echo "\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent");
?>
