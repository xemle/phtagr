<h1><?php echo __('Profile'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'profile')); ?>
<fieldset><legend><?php echo __('General'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.firstname', array('label' => __('First name')));
  echo $this->Form->input('User.lastname', array('label' => __('Last name')));
  echo $this->Form->input('User.email', array('label' => __('Email')));
?>
</fieldset>

<fieldset><legend><?php echo __('Password'); ?></legend>
<?php
  echo $this->Form->input('User.password', array('label' => __('Password')));
  echo $this->Form->input('User.confirm', array('type' => 'password', 'label' => __('Confirm')));
?>
</fieldset>
<fieldset><legend><?php echo __('Others'); ?></legend>
<?php
  echo $this->Form->input('Option.user.browser.full', array('type' => 'checkbox', 'label' => __('Show advanced file browser')));
  $options = array(
    1 => __("Private"),
    2 => __("Group members"),
    3 => __("Users"),
    4 => __("Public")
  );
  echo $this->Form->input('User.visible_level', array('type' => 'select', 'options' => $options, 'label' => __('Profile visibility')));
  $options = array(
    0 => __("Never"),
    1800 => __("Every 30 minutes"),
    3600 => __("Every hour"),
    86400 => __("Every day"),
    604800 => __("Every week"),
    2592000 => __("Every month")
  );
  echo $this->Form->input('User.notify_interval', array('type' => 'select', 'options' => $options, 'label' => __('Send new media notifications')));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
