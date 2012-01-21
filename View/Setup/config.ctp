<h1><?php echo __("Database connection"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("This step creates the configuration for the database connection. Please add your database connection settings here."); ?></p>

<?php echo $this->Form->create(null, array('action' => 'config')); ?>

<fieldset><legend><?php echo __("Database"); ?></legend>
<?php 
  echo $this->Form->input('db.host', array('label' => __('Host', true)));
  echo $this->Form->input('db.database', array('label' => __('Database', true)));
  echo $this->Form->input('db.login', array('label' => __('Username', true)));
  echo $this->Form->input('db.password', array('label' => __('Password', true), 'type' => 'password'));
  echo $this->Form->input('db.prefix', array('label' => __('Prefix', true)));
?>
</fieldset>
<?php echo $this->Form->submit(__('Continue', true)); ?>
</form>
<?php
    $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $(':submit').button();
  });
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
