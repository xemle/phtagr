<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'login')); ?>
<fieldset>
<legend><?php __('Login'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.password', array('label' => __('Password', true)));
?>
</fieldset>
<?php echo $form->end(__('Login', true)); ?>

<?php echo $html->link(__('Forgot your password', true), 'password'); ?>
<?php if ($register): ?>
 <?php printf(__("or %s", true), $html->link(__('sign up', true), 'register')); ?>
<?php endif; ?>
