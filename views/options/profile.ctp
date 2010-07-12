<h1><?php __('Profile'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'profile')); ?>
<fieldset><legend><?php __('General'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.firstname', array('label' => __('First name', true)));
  echo $form->input('User.lastname', array('label' => __('Last name', true)));
  echo $form->input('User.email', array('label' => __('Email', true)));
?>
</fieldset>

<fieldset><legend><?php __('Password'); ?></legend>
<?php
  echo $form->input('User.password', array('label' => __('Password', true)));
  echo $form->input('User.confirm', array('type' => 'password', 'label' => __('Confirm', true)));
?>
</fieldset>
<fieldset><legend><?php __('Others'); ?></legend>
<?php
  echo $form->input('Option.user.browser.full', array('type' => 'checkbox', 'label' => __('Show advanced file browser', true)));
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
