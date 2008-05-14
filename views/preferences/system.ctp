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
<fieldset><legend>Google Maps</legend>
<p>Key for <a href="http://maps.google.com">Google Maps</a></p>
<?php
  echo $form->input('google.map.key', array('label' => "Key")); 
?>
</fieldset>
<?php echo $form->end('Save'); ?>
<?php debug($this->data); ?>
