<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('User', array('action' => 'login', 'class' => 'login')); ?>
<fieldset>
<legend><?php __('Login'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username', true)));
  echo $this->Form->input('User.password', array('label' => __('Password', true)));
?>
</fieldset>
<div class="submit">
<?php 
  $signup = '';
  
  echo $this->Form->submit(__('Login', true), array('div' => false));
  if ($register) {
    echo " ".$this->Html->link(__('Sign Up', true), 'register', array('class' => 'button'))." ";
  }
  echo "<br/>".$this->Html->link(__('Forgot your password', true), 'password');
?>
</div>
<?php 
  echo $this->Form->end();
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.button').button();
    $('.message').addClass("ui-widget ui-corner-all ui-state-highlight");
  });
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
