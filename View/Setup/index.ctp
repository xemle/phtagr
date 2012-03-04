<h1><?php echo __("Welcome"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("Welcome to the installation procedure of phTagr. Following steps are required to install it:"); ?></p>

<ul>
  <li><?php echo __("Check for required paths"); ?></li>
  <li><?php echo __("Configuration of database connection"); ?></li>
  <li><?php echo __("Initialize required tables"); ?></li>
  <li><?php echo __("Create administration account"); ?></li>
  <li><?php echo __("Check required external programs"); ?></li>
</ul>

<p><?php echo __("Note: Cookies are required to install and run phTagr."); ?></p>

<p><?php echo __("This setup will step through the required steps. Please click %s to start the installation.", $this->Html->link(__('continue'), 'path')); ?></p>
