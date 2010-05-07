<h1><?php __("File Upload"); ?></h1>

<?php $session->flash(); ?>

<p><?php printf(__("You upload files to folder %s", true), $fileList->location($path)); ?></p>
<p><?php printf(__("You can upload maximal %s and %s at once", true), $number->toReadableSize($free), $number->toReadableSize($max)); ?></p>

<?php echo $form->create(false, array('action' => 'upload/'.$path, 'type' => 'file')); ?>
<fieldset>
  <?php echo $form->file('File.Filedata'); ?>
  <?php echo $form->input('File.extract', array('label' => __('Extract ZIP archive', true), 'checked' => true, 'type' => 'checkbox')); ?>
</fieldset>

<?php echo $form->end(__("Upload", true)); ?>
