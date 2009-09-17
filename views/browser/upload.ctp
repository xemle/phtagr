<h1>File Upload</h1>
<?php $session->flash(); ?>

<p>You upload files to folder <?php echo $fileList->location($path); ?></p>
<p>You can upload maximal <?php echo $number->toReadableSize($free); ?> and <?php echo $number->toReadableSize($max); ?> at once.</p>

<?php echo $form->create(false, array('action' => 'upload/'.$path, 'type' => 'file')); ?>
<fieldset>
  <?php echo $form->file('File.Filedata'); ?>
  <?php echo $form->input('File.extract', array('label' => 'Extract ZIP archive', 'checked' => true, 'type' => 'checkbox')); ?>
</fieldset>

<?php echo $form->submit("Upload"); ?>
<?php echo $form->end(); ?>

<?php echo $html->link("View folder", 'index/'.$path); ?>
