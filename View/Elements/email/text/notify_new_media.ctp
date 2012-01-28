<?php printf(__("Hi %s!", true), $user['User']['username']); ?>


<?php printf(__("New media available at %s", true), $html->link($url, $url)); ?>


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
    echo sprintf(__("User %s published following media", true), $user) . "\r\n";
    foreach ($userMedia as $m) {
      echo "{$m['Media']['name']} (Link: $url/images/view/{$m['Media']['id']})\r\n";
    }
    echo "\r\n";
  }
?>

<?php printf(__("See all new media at %s", true), $url . '/explorer/view/sort:newest'); ?>


<?php __("Sincerly, your phTagr agent"); ?>


<?php __("PS: If you do not like to receive this notification again, please configure the notification interval in your user profile.") ?>
