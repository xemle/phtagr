<h1><?php __('Password Request'); ?></h1>
<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'password')); ?>
<fieldset><legend><?php __('Account Data'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.email', array('label' => __('Email', true)));
?>
</fieldset>
<?php echo $form->end(__('Submit', true)); ?>
