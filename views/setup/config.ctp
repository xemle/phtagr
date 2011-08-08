<h1><?php __("Database connection"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php __("This step creates the configuration for the database connection. Please add your database connection settings here."); ?></p>

<?php echo $form->create(null, array('action' => 'config', 'class' => 'default')); ?>

<fieldset><legend><?php __("Database"); ?></legend>
<?php 
  echo $form->input('db.host', array('label' => __('Host', true)));
  echo $form->input('db.database', array('label' => __('Database', true)));
  echo $form->input('db.login', array('label' => __('Username', true)));
  echo $form->input('db.password', array('label' => __('Password', true), 'type' => 'password'));
  echo $form->input('db.prefix', array('label' => __('Prefix', true)));
?>
</fieldset>
<?php echo $form->submit(__('Continue', true)); ?>
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
