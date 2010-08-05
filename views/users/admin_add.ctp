<h1><?php __('Add new User'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'add')); ?>
<fieldset><legend><?php __('Create new user'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.email', array('label' => __('Email', true)));
  echo $form->input('User.password', array('label' => __('Password', true)));
  echo $form->input('User.confirm', array('label' => __('Confirm', true), 'type' => 'password'));
?>
</fieldset>
<?php echo $form->end(__("Create", true)); ?>
