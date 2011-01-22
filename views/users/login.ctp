<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'login')); ?>
<fieldset>
<legend><?php __('Login'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.password', array('label' => __('Password', true)));
?>
</fieldset>
<?php 
  $signup = '';
  if ($register) {
    $signup = $html->link(__('Sign Up', true), 'register');
  }
  echo $html->tag('ul', 
    $html->tag('li', $form->submit(__('Login', true)), array('escape' => false))
    . $html->tag('li', $html->link(__('Forgot your password', true), 'password'), array('escape' => false))
    . $signup,
    array('class' => 'buttons', 'escape' => false));
  echo $form->end();
?>
