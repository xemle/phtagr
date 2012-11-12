<?php
  echo __("Hello %s!", $user['User']['username'])."\n";
  echo "\n";
  echo __("Your group subscription of group %s was accepted.", $group['Group']['name'])."\n";
  echo "\n";
  echo __("Group information:")."\n";
  echo "\n";
  echo __("Description: %s", $group['Group']['description'])."\n";
  echo __("Number of members: %d", count($group['Member']))."\n";
  echo "\n";
  echo __("More details of group %s are available at %s.", $group['Group']['name'], Router::url("/groups/view/{$group['Group']['name']}", true))."\n";
  echo "\n";
  echo "\n";
  echo __("Sincerely")."\n";
  echo "\n";
  echo __("Your phTagr Agent")."\n";
?>
