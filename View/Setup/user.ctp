<h1><?php echo __("Create admin account"); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'user')); ?>

<fieldset>
<?php 
  echo $this->Form->input('User.username', array('label' => __("Username", true))); 
  echo $this->Form->input('User.password', array('label' => __("Password", true)));
  echo $this->Form->input('User.confirm', array('label' => __("Confirm", true), 'type' => 'password'));
  echo $this->Form->input('User.email', array('label' => __("Email", true)));
?>
</fieldset>
<?php echo $this->Form->end(__('Create', true)); ?>
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

