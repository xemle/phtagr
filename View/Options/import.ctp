<h1><?php echo __('Import Options'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php echo $this->Form->create(null, array('action' => 'import')); ?>
<fieldset><legend><?php echo __('GPS Tracks'); ?></legend>
<?php
  echo $this->Form->input('filter.gps.offset', array('type' => 'text', 'label' => __("Time offset (minutes)"))); 
  echo $this->Form->input('filter.gps.range', array('type' => 'text',  'label' => __("Coordinate time range (minutes)"))); 
  echo $this->Form->input('filter.gps.overwrite', array('type' => 'checkbox',  'label' => __("Overwrite existing coordinates?"))); 
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
