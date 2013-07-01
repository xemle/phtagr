<h1><?php echo __('Sytem Settings'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => 'external')); ?>
<fieldset><legend><?php echo __('External Programs'); ?></legend>
<?php
  echo $this->Form->input('bin.exiftool', array('label' => __("Path to %s", "exiftool")));
  echo $this->Form->input('bin.convert', array('label' => __("Path to %s", "convert")));
  echo $this->Form->input('bin.ffmpeg', array('label' => __("Path to %s", "ffmpeg")));
  echo $this->Form->input('bin.flvtool2', array('label' => __("Path to %s", "flvtool2")));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
