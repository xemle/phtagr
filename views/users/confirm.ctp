<?php $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'confirm')); ?>
<fieldset>
<legend>Account Confirmation</legend>
<p>Please insert your confirmation key to finalize the account creation.</p>
<?php
  echo $form->input('User.key');
?>
</fieldset>
<?php echo $form->submit('Confirm'); ?>
</form>

