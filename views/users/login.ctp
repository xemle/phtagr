<?php $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'login')); ?>
<fieldset>
<legend>Login</legend>
<?php
  echo $form->input('User.username');
  echo $form->input('User.password');
?>
</fieldset>
<?php echo $form->submit('Login'); ?>
</form>

