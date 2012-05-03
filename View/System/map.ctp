<h1><?php echo __('Map Settings'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'map')); ?>
<fieldset><legend><?php echo __('Google Maps'); ?></legend>
<p><?php echo __('Enter here your API map key for %s. You can signup %s for a new key. No API map key is required for local setup with localhost.', $this->Html->link('Google Maps', "http://maps.google.com"), $this->Html->link(__('here'), "http://code.google.com/apis/maps/signup.html")); ?></p>
<?php
  echo $this->Form->input('google.map.key', array('label' => __("API key")));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
