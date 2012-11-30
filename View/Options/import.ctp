<h1><?php echo __('Import Options'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php echo $this->Form->create(null, array('action' => 'import')); ?>

<fieldset><legend><?php echo __('Default Access Rights'); ?></legend>
<p><?php echo __('The following access rights are applied to new images.'); ?></p>
<?php
  $aclSelect = array(
    ACL_LEVEL_OTHER => __('Everyone'),
    ACL_LEVEL_USER => __('User'),
    ACL_LEVEL_GROUP => __('Group Members'),
    ACL_LEVEL_PRIVATE => __('Me only'));
  echo $this->Form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can view the image?")));
  echo $this->Form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can download the image?")));
  echo $this->Form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can add tags?")));
  echo $this->Form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'label' => __("Who can edit all meta data?")));
  echo $this->Form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => __("Default image group?")));
?>
</fieldset>

<fieldset><legend><?php echo __('GPS Tracks'); ?></legend>
<p><?php echo __('Usually GPS logs are in UTC while camera time is set to local time. Set time offset to correct time difference between GPS logs and your media.'); ?></p>
<?php
  echo $this->Form->input('filter.gps.offset', array('type' => 'text', 'label' => __("Time offset (minutes)")));
  echo $this->Form->input('filter.gps.range', array('type' => 'text',  'label' => __("Coordinate time range (minutes)")));
  echo $this->Form->input('filter.gps.overwrite', array('type' => 'checkbox',  'label' => __("Overwrite existing coordinates?")));
?>
</fieldset>

<fieldset><legend><?php echo __('Sidecar XMP options'); ?></legend>
<p><?php echo __('Import from and save to sidecar XMP files - only for images (jpg):'); ?></p>
<?php
  echo $this->Form->input('xmp.use.sidecar', array('type' => 'checkbox',  'label' => __("Use sidecar XMP files?")));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
