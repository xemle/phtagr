<p><?php printf(__("Hi %s!", true), $user['User']['username']); ?></p>

<p><?php printf(__("New media available at %s", true), $html->link($url, $url)); ?>.</p>

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
    echo "<p>" . sprintf(__("User %s published following media", true), $html->link($user, $url . '/explorer/user/' . $user)) . "</p>";
    echo "<p>";
    foreach ($userMedia as $m) {
      echo $html->link(
        $html->tag('img', null, array(
          'src' => 'cid:media-' . $m['Media']['id'] . '.jpg', 
          'alt' => $m['Media']['name'])), 
        $url . '/images/view/' . $m['Media']['id'] . '/sort:newest', array('escape' => false)) . "\n";
    }
    echo "</p>";
  }
?>

<p><?php printf(__("See all new media at %s", true), $html->link('media explorer', $url . '/explorer/view/sort:newest')); ?></p>
<p></p>
<p><?php __("Sincerly, your phTagr agent"); ?></p>

<p><i><?php __("PS: If you do not like to receive this notification again, please configure the notification interval in your user profile.") ?></i></p>
