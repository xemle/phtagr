<h1><?php echo __("External Programs"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("Congratulation! phTagr just runs fine now!"); ?></p>

<p><?php echo __("phTagr can use <u>optional</u> external programs to enlarge the functionality. exiftool is used to export the meta data to images like tags, categories or (geo) locations. ffmpeg supports other video file formats like AVI, MPEG, or MOV. And convert supports better thumbnail creation for bigger photos."); ?></p>

<p><?php echo __("You can set the file path of these exteranl programs here or set them later in the system preferences."); ?></p>

<?php echo $this->Html->link(__("Skip these settings"), array("action" => "finish"), array('class' => 'button')); ?>

<?php if (count($missing)): ?>
<div class="info">
<?php echo __("Following programs could not be found: %s.", implode(', ', $missing)); ?>
</div>
<?php endif; ?>
<?php if (isset($mp3Support) && !$mp3Support): ?>
<div class="warning">
<?php echo __("FFMPEG does not support the MP3 audio format. Sound will be disabled for videos!"); ?>
</div>
<?php endif; ?>

<?php echo $this->Form->create(null, array('url' => '/setup/system')); ?>
<fieldset>
<?php
  echo $this->Form->input('bin.exiftool', array('label' => __("Path to %s", "exiftool")));
  echo $this->Form->input('bin.convert', array('label' => __("Path to %s", "convert")));
  echo $this->Form->input('bin.ffmpeg', array('label' => __("Path to %s", "ffmpeg")));
  echo $this->Form->input('bin.flvtool2', array('label' => __("Path to %s", "flvtool2")));
?>
</fieldset>
<?php echo $this->Form->end(__('Save')); ?>
<?php
  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.button').button();
  });
})(jQuery);
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

