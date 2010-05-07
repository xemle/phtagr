<h1><?php __("Create Folder"); ?></h1>

<?php $session->flash(); ?>

<p><?php printf(__("Location %s", true), $fileList->location($path)); ?></p>

<?php echo $form->create(false, array('action' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $form->input('Folder.name', array ('label' => __("Folder Name", true))); ?>
</fieldset>
<?php echo $form->submit(__("Create", true)); ?>
<?php echo $form->end(); ?>
