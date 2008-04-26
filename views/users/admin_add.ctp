<h1>Add new User</h1>

<?php $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'add')); ?>
<fieldset><legend>Create new user</legend>
<?php
  echo $form->input('User.username');
  echo $form->input('User.password');
?>
</fieldset>
<?php echo $form->submit("Create"); ?>
</form>
