<h1><?php __('Guest Creation'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create('Guest', array('action' => 'create')); ?>

<fieldset><legend><?php __('Create new guest account'); ?></legend>
<?php
  echo $form->input('Guest.username', array('label' => __('Username', true)));
  echo $form->input('Guest.email', array('label' => __('Email', true)));
  echo $form->input('Guest.password', array('label' => __('Password', true)));
  echo $form->input('Guest.confirm', array('type' => 'password', 'label' => __('Confirm', true)));
?>
</fieldset>
<?php echo $form->end(__('Create', true)); ?>
