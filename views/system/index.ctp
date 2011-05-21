<h1><?php __('General'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create(null, array('action' => 'index')); ?>
<fieldset><legend><?php __('General'); ?></legend>
<?php
  echo $form->input('general.title', array('label' => __('Gallery title', true)));
  echo $form->input('general.subtitle', array('label' => __('Gallery subtitle', true)));
?>
</fieldset>

<?php echo $form->end(__('Save', true)); ?>
