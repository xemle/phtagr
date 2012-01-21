<h1><?php __("Security settings"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php printf(__("An error occured while setting the security salt. Please have look to the log files and click on %s", true), $html->link(__('continue', true), 'salt')); ?></p>
