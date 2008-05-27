<?php $session->flash(); ?>

<h1>Create Admin Account</h1>

<?php echo $form->create(null, array('action' => 'user')); ?>

<fieldset>
<?php 
  echo $form->input('User.username'); 
  echo $form->input('User.password');
  echo $form->input('User.confirm', array('type' => 'password'));
  echo $form->input('User.email');
?>
</fieldset>
<?php echo $form->submit('Create'); ?>

</form>

