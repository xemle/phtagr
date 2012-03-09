<h1><?php echo __("Welcome"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("Welcome to the installation setup of phTagr. Following steps are required to install it:"); ?></p>

<ul>
  <li><?php echo __("Initial file and directory check"); ?></li>
  <li><?php echo __("Configuration of database connection"); ?></li>
  <li><?php echo __("Initialize required tables"); ?></li>
  <li><?php echo __("Administration account creation"); ?></li>
  <li><?php echo __("Configuration of external tools"); ?></li>
</ul>

<p><?php echo __("Note: Cookies are required to install and run phTagr."); ?></p>

<p><?php echo __("This setup will step through the required steps."); ?>
<p><?php echo $this->Html->link(__('Start Installation'), 'path', array('class' => 'button')); ?></p>
<?php
$script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $('.button').button();
  });
})(jQuery);
SCRIPT;
echo $this->Html->scriptBlock($script, array('inline' => false));
?>
