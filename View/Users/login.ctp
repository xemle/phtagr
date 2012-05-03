<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('User', array('action' => 'login', 'class' => 'login')); ?>
<fieldset>
<legend><?php echo __('Login'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.password', array('label' => __('Password')));
?>
</fieldset>
<div class="submit">
<?php
  $signup = '';

  echo $this->Form->submit(__('Login'), array('div' => false));
  if ($register) {
    echo " ".$this->Html->link(__('Sign Up'), 'register', array('class' => 'button'))." ";
  }
  echo "<br/>".$this->Html->link(__('Forgot your password'), 'password');
?>
</div>
<?php
  echo $this->Form->end();
  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.button').button();
    $('.message').addClass("ui-widget ui-corner-all ui-state-highlight");
  });
})(jQuery);
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
