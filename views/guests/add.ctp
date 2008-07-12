<h1>Guest Creation</h1>

<?php $session->flash(); ?>

<?php echo $form->create('Guest', array('action' => 'add')); ?>

<fieldset><legend>Create new guest account</legend>
<?php
  echo $form->input('Guest.username');
  echo $form->input('Guest.email');
  echo $form->input('Guest.password');
  echo $form->input('Guest.confirm', array('type' => 'password'));
?>
</fieldset>
<?php echo $form->submit('Create'); ?>
</form>
