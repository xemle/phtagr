<?php 
  echo __("Hello")."\n";
  echo "\n";
  echo __("A new comment on %s was submitted.", $data['Media']['name'])."\n";
  echo "\n";
  echo __("From: %s", $data['Comment']['name'])."\n";
  echo __("Date: %s", $data['Comment']['date'])."\n";
  echo __("Link: %s", Router::url('/images/view/'.$data['Media']['id'], true))."\n";
  echo "\n";
  echo $data['Comment']['text']."\n";
  echo "\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent")."\n";
  echo "\n";
  echo __("PS: This mail is a comment notification of your previous comment(s) on this media.")."\n";
?>
