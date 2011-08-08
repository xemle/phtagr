<h1><?php __("External Programs"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php __("Congratulation! phTagr just runs fine now!"); ?></p>

<p><?php __("phTagr can use <u>optional</u> external programs to enlarge the functionality. exiftool is used to export the meta data to images like tags, categories or (geo) locations. ffmpeg supports other video file formats like AVI, MPEG, or MOV. And convert supports better thumbnail creation for bigger photos."); ?></p>

<p><?php __("You can set the file path of these exteranl programs here or set them later in the system preferences."); ?></p>

<?php echo $html->link(__("Skip these settings", true), array("action" => "finish"), array('class' => 'button')); ?>

<?php if (count($missing)): ?>
<div class="info">
<?php printf(__("Following programs could not be found: %s.", true), implode(', ', $missing)); ?> 
</div>
<?php endif; ?>
<?php if (isset($mp3Support) && !$mp3Support): ?>
<div class="warning">
<?php __("FFMPEG does not support the MP3 audio format. Sound will be disabled for videos!"); ?>
</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'system', 'class' => 'default')); ?>
<fieldset>
<?php 
  echo $form->input('bin.exiftool', array('label' => sprintf(__("Path to %s", true), "exiftool")));
  echo $form->input('bin.convert', array('label' => sprintf(__("Path to %s", true), "convert")));
  echo $form->input('bin.ffmpeg', array('label' => sprintf(__("Path to %s", true), "ffmpeg")));
  echo $form->input('bin.flvtool2', array('label' => sprintf(__("Path to %s", true), "flvtool2")));
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
<?php
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.button').button();
  });
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

