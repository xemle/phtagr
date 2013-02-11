<h1><?php echo __('User Registration'); ?></h1>
<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'register')); ?>
<fieldset><legend><?php echo __('Registration'); ?></legend>
<?php echo $this->Form->input('user.register.enable', array('label' => __('Allow anonymous registration'), 'type' => 'checkbox')); ?>
<?php echo $this->Form->input('user.register.quota', array('label' => __('Initial quota limit'), 'type' => 'text', 'value' => $this->Number->toReadableSize($this->request->data['user']['register']['quota']))); ?>
</fieldset>
<fieldset><legend><?php echo __('User logging'); ?></legend>
<?php echo $this->Form->input('user.logging.enable', array('label' => __('Enable logging'), 'type' => 'checkbox')); ?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
