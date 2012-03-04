<?php echo __("Hi %s!", $user['User']['username']); ?>


<?php echo __("New media available at %s", $this->Html->link($url, $url)); ?>


<?php 
  $userToMedia = array();
  foreach ($media as $m) {
    $username = $m['User']['username'];
    if (!isset($userToMedia[$username])) {
      $userToMedia[$username] = array();
    }
    $userToMedia[$username][] = $m;
  }
  
  foreach ($userToMedia as $user => $userMedia) {
    echo __("User %s published following media", $user) . "\r\n";
    foreach ($userMedia as $m) {
      echo "{$m['Media']['name']} (Link: $url/images/view/{$m['Media']['id']})\r\n";
    }
    echo "\r\n";
  }
?>

<?php echo __("See all new media at %s", $url . '/explorer/view/sort:newest'); ?>


<?php echo __("Sincerly, your phTagr agent"); ?>


<?php echo __("PS: If you do not like to receive this notification again, please configure the notification interval in your user profile.") ?>
