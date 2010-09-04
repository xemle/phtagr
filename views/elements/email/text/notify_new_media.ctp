Hi <?php echo $user['User']['username']; ?>!

There are new media available at <?php echo $url; ?>.

<?php foreach($media as $m): ?>
<?php printf("%s published %s (link: %s)\n", $m['User']['username'], $m['Media']['name'], $url . '/images/view/' . $m['Media']['id']); ?>
<?php endforeach; ?>

See all new media at <?php echo $url . '/explorer'; ?>


Sincerly, your phTagr agent

PS: If you do not like to receive this notification again, please configure the notification interval in your user profile.
