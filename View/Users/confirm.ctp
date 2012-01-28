<?php echo $session->flash(); ?>

<?php echo $form->create('User', array('action' => 'confirm')); ?>
<fieldset>
<legend><?php __('Account Confirmation'); ?></legend>
<p><?php __('Please insert your confirmation key to finalize the account creation.'); ?></p>
<?php
  echo $form->input('User.key', array('label' => __('Key', true)));
?>
</fieldset>
<?php echo $form->end(__('Confirm', true)); ?>

