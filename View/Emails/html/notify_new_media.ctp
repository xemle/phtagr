<p><?php echo __("Hi %s!", $user['User']['username']); ?></p>

<p><?php echo __("New media available at %s", $this->Html->link($url, $url)); ?>.</p>

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
    echo "<p>" . __("User %s published following media", $this->Html->link($user, $url . '/explorer/user/' . $user)) . "</p>";
    echo "<p>";
    foreach ($userMedia as $m) {
      echo $this->Html->link(
        $this->Html->tag('img', null, array(
          'src' => 'cid:media-' . $m['Media']['id'] . '.jpg',
          'alt' => $m['Media']['name'])),
        $url . '/images/view/' . $m['Media']['id'] . '/sort:newest', array('escape' => false)) . "\n";
    }
    echo "</p>";
  }
?>

<p><?php echo __("See all new media at %s", $this->Html->link('media explorer', $url . '/explorer/view/sort:newest')); ?></p>
<p></p>
<p><?php echo __("Sincerly, your phTagr agent"); ?></p>

<p><i><?php echo __("PS: If you do not like to receive this notification again, please configure the notification interval in your user profile.") ?></i></p>
