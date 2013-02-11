<h1><?php echo __('Export Options'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php echo $this->Form->create(null, array('action' => 'export')); ?>

<fieldset><legend><?php echo __('Video Options'); ?></legend>
<?php
  echo $this->Form->input('filter.video.createThumb', array('type' => 'checkbox',  'label' => __("Create video thumbnail (THM)")));
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
