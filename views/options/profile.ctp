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
  $options = array(
    1 => __("Private", true),
    2 => __("Group members", true),
    3 => __("Users", true),
    4 => __("Public", true)
  );
  echo $form->input('User.visible_level', array('type' => 'select', 'options' => $options, 'label' => __('Profile visibility', true)));
  $options = array(
    0 => __("Never", true),
    1800 => __("Every 30 minutes", true),
    3600 => __("Every hour", true),
    86400 => __("Every day", true),
    604800 => __("Every week", true),
    2592000 => __("Every month", true)
  );
  echo $form->input('User.notify_interval', array('type' => 'select', 'options' => $options, 'label' => __('Send new media notifications', true)));
?>
</fieldset>
<?php 
  echo $form->submit(__('Save', true), array('class' => 'ui-button')); 
  echo $form->end();
?>
