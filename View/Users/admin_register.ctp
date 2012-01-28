<h1><?php __('User Registration'); ?></h1>
<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'register')); ?>
<fieldset><legend><?php __('Registration'); ?></legend>
<?php echo $form->input('user.register.enable', array('label' => __('Allow anonymous registration', true), 'type' => 'checkbox')); ?>
<?php echo $form->input('user.register.quota', array('label' => __('Initial quota limit', true), 'type' => 'text', 'value' => $number->toReadableSize($this->data['user']['register']['quota']))); ?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
