<?php $session->flash(); ?>

<h1>Security</h1>

<p>An error occured while setting the security salt. Please have look to the
log files and <?php echo $html->link('retry', 'salt'); ?></p>
