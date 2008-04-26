<h1>Sytem Settings</h1>
<?php $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'system')); ?>
<fieldset><legend>External Program Paths</legend>
<?php
  echo $form->input('bin.exiftool', array('label' => "Path to exiftool")); 
  echo $form->input('bin.convert', array('label' => "Path to convert")); 
  echo $form->input('bin.ffmpeg', array('label' => "Path to ffmpeg")); 
  echo $form->input('bin.flvtool2', array('label' => "Path to flvtool2")); 
?>
</fieldset>
<?php echo $form->end('Save'); ?>
<?php debug($this->data); ?>
