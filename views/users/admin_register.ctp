<h1>User Registration</h1>
<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'register')); ?>
<fieldset><legend>Registration</legend>
<?php echo $form->input('user.register.enable', array('label' => 'Allow anonymous registration', 'type' => 'checkbox')); ?>
<?php echo $form->input('user.register.quota', array('label' => 'Initial quota limit', 'type' => 'text', 'value' => $number->toReadableSize($this->data['user']['register']['quota']))); ?>
</fieldset>
<?php echo $form->end('save'); ?>
