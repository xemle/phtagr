<h1><?php echo __("Create admin account"); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => '/setup/user')); ?>

<fieldset>
<?php
  echo $this->Form->input('User.username', array('label' => __("Username")));
  echo $this->Form->input('User.password', array('label' => __("Password")));
  echo $this->Form->input('User.confirm', array('label' => __("Confirm"), 'type' => 'password'));
  echo $this->Form->input('User.email', array('label' => __("Email")));
?>
</fieldset>
<?php echo $this->Form->end(__('Create')); ?>
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

