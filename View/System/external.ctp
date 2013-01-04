<h1><?php echo __('Sytem Settings'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('action' => 'external')); ?>
<fieldset><legend><?php echo __('External Programs'); ?></legend>
<?php
  echo $this->Form->input('bin.exiftool', array('label' => __("Path to %s", "exiftool")));
  echo $this->Form->input('bin.convert', array('label' => __("Path to %s", "convert")));
  echo $this->Form->input('bin.ffmpeg', array('label' => __("Path to %s", "ffmpeg")));
  echo $this->Form->input('bin.flvtool2', array('label' => __("Path to %s", "flvtool2")));
?>
</fieldset>
<fieldset><legend><?php echo __('Exiftool Options'); ?></legend>
<?php
  $description = __("Option -stayOpen enables fast reading and writing of meta data. This option is experimental.");
  echo $this->Form->input('bin.exiftoolOption.stayOpen', array('type' => 'checkbox', 'label' => __("Enable -stayOpen"), 'after' => "<p class=\"description\">$description</p>"));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
