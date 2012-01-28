<h1><?php echo __(" User: %s", $this->request->data['User']['username']); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'edit/'.$this->request->data['User']['id'])); ?>
<fieldset><legend><?php echo __('General'); ?></legend>
<?php
  echo $this->Form->input('User.firstname', array('label' => __('First name')));
  echo $this->Form->input('User.lastname', array('label' => __('Last name')));
  echo $this->Form->input('User.email', array('label' => __('Email')));
  $roles = array(ROLE_USER => __('User'), ROLE_SYSOP => __('System Operator'));
  if ($allowAdminRole) {
    $roles[ROLE_ADMIN] = __('Admin');
  }
  echo $this->Form->input('User.role', array('type' => 'select', 'options' => $roles, 'selected' => $this->request->data['User']['role']));
?>
</fieldset>

<fieldset><legend>Other</legend>
<?php
  echo $this->Form->input('User.expires', array('label' => __('Expire date'), 'type' => 'text'));
  echo $this->Form->input('User.quota', array('type' => 'text', 'label' => __('Upload Quota'), 'value' => $this->Number->toReadableSize($this->request->data['User']['quota'])));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
