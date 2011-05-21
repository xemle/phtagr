<h1><?php __('Map Settings'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'map')); ?>
<fieldset><legend><?php __('Google Maps'); ?></legend>
<p><?php printf(__('Enter here your API map key for %s. You can signup %s for a new key. No API map key is required for local setup with localhost.', true), $html->link('Google Maps', "http://maps.google.com"), $html->link(__('here', true), "http://code.google.com/apis/maps/signup.html")); ?></p>
<?php
  echo $form->input('google.map.key', array('label' => __("API key", true))); 
?>
</fieldset>
<?php echo $form->end(__('Save', true)); ?>
