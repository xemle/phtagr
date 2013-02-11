<?php
  echo __("Hello %s!", $group['User']['username'])."\n";
  echo "\n";
  echo __("User %s joined your group %s", $user['User']['username'], $group['Group']['name'])."\n";
  echo "\n";
  echo __("To see more information about user %s, please visit %s.", $user['User']['username'], Router::url("/users/view/{$user['User']['username']}", true))."\n";
  echo "\n";
  echo __("Group info:")."\n";
  echo "\n";
  echo __("Description: %s", $group['Group']['description'])."\n";
  echo __("Number of members: %d", count($group['Member']))."\n";
  echo "\n";
  echo __("More details of your group %s are available at %s", $group['Group']['name'], Router::url("/groups/view/{$group['Group']['name']}", true))."\n";
  echo "\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent");
?>
