<?php $session->flash(); ?>

<h1>Check System</h1>

<?php if (count($missing)): ?>
<div class="info">
Following required programs could not be found: <?php echo implode(', ', $missing); ?>. Please install the programs or set the correct executeable path below.
</div>
<?php endif; ?>
<?php if (isset($mp3Support) && !$mp3Support): ?>
<div class="warning">
FFMPEG does not support the MP3 audio format. Sound will be disabled for videos!
</div>
<?php endif; ?>

<p>phTagr requires external programs to create thumbnails, flash videos, etc. Please set the proper commands here.</p>

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
