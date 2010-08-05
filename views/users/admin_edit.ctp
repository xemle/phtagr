<h1><?php printf(__(" User: %s", true), $this->data['User']['username']); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'edit/'.$this->data['User']['id'])); ?>
<fieldset><legend><?php __('General'); ?></legend>
<?php
  echo $form->input('User.firstname', array('label' => __('First name', true)));
  echo $form->input('User.lastname', array('label' => __('Last name', true)));
  echo $form->input('User.email', array('label' => __('Email', true)));
  $roles = array(ROLE_USER => __('User', true), ROLE_SYSOP => __('System Operator', true));
  if ($allowAdminRole) {
    $roles[ROLE_ADMIN] = __('Admin', true);
  }
  echo $form->input('User.role', array('type' => 'select', 'options' => $roles, 'selected' => $this->data['User']['role']));
?>
</fieldset>

<fieldset><legend>Password</legend>
<?php
  echo $form->input('User.password', array('label' => __('Password', true)));
  echo $form->input('User.confirm', array('label' => __('Confirm', true), 'type' => 'password'));
?>
</fieldset>

<fieldset><legend>Other</legend>
<?php
  echo $form->input('User.expires', array('label' => __('Expire date', true), 'type' => 'text'));
  echo $form->input('User.quota', array('type' => 'text', 'label' => __('Upload Quota', true), 'value' => $number->toReadableSize($this->data['User']['quota'])));
?>
</fieldset>

<?php echo $form->end(__('Save', true)); ?>
