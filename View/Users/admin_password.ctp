<h1><?php printf(__(" User: %s", true), $this->data['User']['username']); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'password/'.$this->data['User']['id'])); ?>
<fieldset><legend>Password</legend>
<?php
  echo $form->input('User.password', array('label' => __('Password', true)));
  echo $form->input('User.confirm', array('label' => __('Confirm', true), 'type' => 'password'));
?>
</fieldset>

<?php echo $form->end(__('Save', true)); ?>
