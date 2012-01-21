<h1><?php __("Welcome"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php __("Welcome to the installation procedure of phTagr. Following steps are required to install it:"); ?></p>

<ul>
  <li><?php __("Check for required paths"); ?></li>
  <li><?php __("Configuration of database connection"); ?></li>
  <li><?php __("Initialize required tables"); ?></li>
  <li><?php __("Create administration account"); ?></li>
  <li><?php __("Check required external programs"); ?></li>
</ul>

<p><?php __("Note: Cookies are required to install and run phTagr."); ?></p>

<p><?php printf(__("This setup will step through the required steps. Please click %s to start the installation.", true), $html->link(__('continue', true), 'path')); ?></p>
