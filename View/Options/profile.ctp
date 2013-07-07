<h1><?php echo __('Profile'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => 'profile')); ?>
<fieldset><legend><?php echo __('General'); ?></legend>
<?php
  echo $this->Form->input('User.username', array('label' => __('Username')));
  echo $this->Form->input('User.firstname', array('label' => __('First name')));
  echo $this->Form->input('User.lastname', array('label' => __('Last name')));
  echo $this->Form->input('User.email', array('label' => __('Email')));
?>
</fieldset>

<fieldset><legend><?php echo __('Others'); ?></legend>
<?php
  $options = array(
    PROFILE_LEVEL_PRIVATE => __("Private"),
    PROFILE_LEVEL_GROUP => __("Group members"),
    PROFILE_LEVEL_USER => __("Users"),
    PROFILE_LEVEL_PUBLIC => __("Public")
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
