<h1>Profile</h1>

<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'profile')); ?>
<fieldset><legend>General</legend>
<?php
  echo $form->input('User.username');
  echo $form->input('User.firstname');
  echo $form->input('User.lastname');
  echo $form->input('User.email');
?>
</fieldset>

<fieldset><legend>Password</legend>
<?php
  echo $form->input('User.password');
  echo $form->input('User.confirm', array('type' => 'password'));
?>
</fieldset>
<?php echo $form->submit('Save'); ?>
<?php echo $form->end(); ?>
