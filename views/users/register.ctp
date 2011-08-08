<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'register', 'class' => 'default')); ?>
<fieldset>
<legend><?php __('Create Account'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.password', array('label' => __('Password', true)));
  echo $form->input('User.confirm', array('label' => __('Confirm', true), 'type' => 'password'));
  echo $form->input('User.email', array('label' => __('Email', true)));
  echo '<div class="input text"><label>&nbsp;</label><img src="'.$html->url('/users/captcha/verify.jpg').'" /></div>';
  echo $form->input('Captcha.verification');
?>
</fieldset>
<?php 
  echo $form->end(__('Sign Up', true)); 

  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.message').addClass("ui-widget ui-corner-all ui-state-highlight");
  }); 
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
