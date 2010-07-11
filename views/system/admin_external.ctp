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
<fieldset><legend><?php __('Google Maps'); ?></legend>
<p><?php printf(__('Enter here your API map key for %s. You can signup %s for a new key. No API map key is required for local setup with localhost.', true), $html->link('Google Maps', "http://maps.google.com"), $html->link(__('here', true), "http://code.google.com/apis/maps/signup.html")); ?></p>
<?php
  echo $form->input('google.map.key', array('label' => __("API key", true))); 
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
