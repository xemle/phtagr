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

<fieldset><legend><?php echo __('Meta Data Export Option'); ?></legend>
<p><?php echo __('phTagr can write meta data from your media files. The meta data can be written embedded into the media file itself or it can be stored in a XMP sidecar file. This feature requires Exiftool to be configured properly.'); ?></p>
<p><?php echo __('If you enable write support of meta data your media files are modified by phTagr. While these features are well tested please use them with caution. Creating backup files is recommended (as always).'); ?></p>
<?php
  echo $this->Form->input('filter.write.metadata.embedded', array('type' => 'checkbox',  'label' => __("Write and embedd meta data into media files")));
  echo $this->Form->input('filter.write.metadata.sidecar', array('type' => 'checkbox',  'label' => __("Write meta data to XMP sidecar files")));
  echo $this->Form->input('filter.create.metadata.sidecar', array('type' => 'checkbox',  'label' => __("Create XMP sidecar file if missing")));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
