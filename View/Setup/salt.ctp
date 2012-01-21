<h1><?php echo __("Security settings"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("An error occured while setting the security salt. Please have look to the log files and click on %s", $this->Html->link(__('continue'), 'salt')); ?></p>
