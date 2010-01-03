<?php $session->flash(); ?>

<h1>External Programs</h1>

<p>Congratulation! phTagr just runs fine now!</p>

<p>phTagr can use <u>optional</u> external programs to enlarge the functionality. exiftool is used to export the meta data to images like tags, categories or (geo) locations. ffmpeg supports other video file formats like AVI, MPEG, or MOV. And convert supports better thumbnail creation for bigger photos.</p>

<p>You can set the file path of these exteranl programs here or set them later in the system preferences.</p>

<p><?php echo $html->link("Skip these settings", array("action" => "finish")); ?></p>

<?php if (count($missing)): ?>
<div class="info">
Following programs could not be found: <?php echo implode(', ', $missing); ?>. 
</div>
<?php endif; ?>
<?php if (isset($mp3Support) && !$mp3Support): ?>
<div class="warning">
FFMPEG does not support the MP3 audio format. Sound will be disabled for videos!
</div>
<?php endif; ?>

<?php echo $form->create(null, array('action' => 'system')); ?>
<fieldset>
<?php 
  echo $form->input('bin.exiftool', array('label' => "Path to exiftool"));
  echo $form->input('bin.convert', array('label' => "Path to convert"));
  echo $form->input('bin.ffmpeg', array('label' => "Path to ffmpeg"));
  echo $form->input('bin.flvtool2', array('label' => "Path to flvtool2"));
?>
</fieldset>
<?php echo $form->submit('Save'); ?>
