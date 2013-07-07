<h1><?php echo __("Database connection"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("This step creates the configuration for the database connection. Please add your database connection settings here."); ?></p>

<?php echo $this->Form->create(null, array('url' => 'config')); ?>

<fieldset><legend><?php echo __("Database"); ?></legend>
  <?php
  echo $this->Form->input('host', array('label' => __('Host')));
  echo $this->Form->input('database', array('label' => __('Database')));
  echo $this->Form->input('login', array('label' => __('Username')));
  echo $this->Form->input('password', array('label' => __('Password'), 'type' => 'password'));
  echo $this->Form->input('prefix', array('label' => __('Prefix')));
  ?>
</fieldset>
<?php echo $this->Form->end(__('Continue')); ?>
<?php
$script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $(':submit').button();
  });
})(jQuery);
SCRIPT;
echo $this->Html->scriptBlock($script, array('inline' => false));
?>
