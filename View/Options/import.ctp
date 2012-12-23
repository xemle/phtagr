<h1><?php echo __('Import Options'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php echo $this->Form->create(null, array('action' => 'import')); ?>

<fieldset><legend><?php echo __('Default Access Rights'); ?></legend>
<p><?php echo __('The following access rights are applied to new images.'); ?></p>
<?php
  $aclSelect = array(
    ACL_LEVEL_PRIVATE => __('Me'),
    ACL_LEVEL_GROUP => __('Group'),
    ACL_LEVEL_USER => __('Users'),
    ACL_LEVEL_OTHER => __('All'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can view the image?")).
    $this->Html->tag('div', $this->Form->radio('acl.read.preview', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can download the image?")).
    $this->Html->tag('div', $this->Form->radio('acl.read.original', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can add tags?")).
    $this->Html->tag('div', $this->Form->radio('acl.write.tag', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can edit all meta data?")).
    $this->Html->tag('div', $this->Form->radio('acl.write.meta', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));

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
  echo $this->Form->input('filter.write.metadata.embedded', array('type' => 'checkbox',  'label' => __("Write meta data embedded into media files")));
  echo $this->Form->input('filter.write.metadata.sidecar', array('type' => 'checkbox',  'label' => __("Write meta data to XMP sidecar files")));
  echo $this->Form->input('filter.create.metadata.sidecar', array('type' => 'checkbox',  'label' => __("Create XMP sidecar file if missing (for all files)")));
  echo $this->Form->input('filter.create.nonEmbeddableFile.metadata.sidecar', array('type' => 'checkbox',  'label' => __("Create XMP sidecar file if missing for non JPEG files only")));
?>
<p><?php echo __('Meta data are not written immediatly to files for performance. They are written manually by %s. If a file is requested by a download action the meta data can be updated.', $this->Html->link(__("Meta Data Sync"), '/browser/sync')); ?></p>
<?php
  echo $this->Form->input('filter.write.onDemand', array('type' => 'checkbox',  'label' => __("Write meta data on demand")));
?>

</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
