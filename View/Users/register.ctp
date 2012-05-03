<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('User', array('action' => 'register')); ?>
<fieldset>
<legend><?php echo __('Create Account'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.password', array('label' => __('Password')));
  echo $this->Form->input('User.confirm', array('label' => __('Confirm'), 'type' => 'password'));
  echo $this->Form->input('User.email', array('label' => __('Email')));
  echo '<div class="input text"><label>&nbsp;</label><img src="'.$this->Html->url('/users/captcha/verify.jpg').'" /></div>';
  echo $this->Form->input('Captcha.verification');
?>
</fieldset>
<?php
  echo $this->Form->end(__('Sign Up'));

  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.message').addClass("ui-widget ui-corner-all ui-state-highlight");
  });
})(jQuery);
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
