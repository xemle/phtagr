<h1><?php echo __('Password Request'); ?></h1>
<?php echo $this->Session->flash(); ?>

<p><?php echo __("Please insert your username and email address to request your lost password."); ?>

<?php echo $this->Form->create('User', array('action' => 'password')); ?>
<fieldset><legend><?php echo __('Account Data'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.email', array('label' => __('Email')));
?>
</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
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
