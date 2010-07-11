<h1>Password Request</h1>
<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'password')); ?>
<fieldset><legend>Account Data</legend>
<?php
  echo $form->input('User.username');
  echo $form->input('User.email');
?>
</fieldset>
<?php echo $form->submit('Submit'); ?>
</form>
