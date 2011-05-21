<h1><?php __('Sytem Settings'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'external')); ?>
<fieldset><legend><?php __('External Programs'); ?></legend>
<?php
  echo $form->input('bin.exiftool', array('label' => sprintf(__("Path to %s", true), "exiftool"))); 
  echo $form->input('bin.convert', array('label' => sprintf(__("Path to %s", true), "convert"))); 
  echo $form->input('bin.ffmpeg', array('label' => sprintf(__("Path to %s", true), "ffmpeg"))); 
  echo $form->input('bin.flvtool2', array('label' => sprintf(__("Path to %s", true), "flvtool2"))); 
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
