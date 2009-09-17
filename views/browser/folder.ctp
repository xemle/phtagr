<h1>Create Folder</h1>
<?php $session->flash(); ?>

<p>Location <?php echo $fileList->location($path); ?></p>

<?php echo $form->create(false, array('action' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $form->input('Folder.name', array ('label' => "Folder Name")); ?>
</fieldset>
<?php echo $form->submit("Create"); ?>
<?php echo $form->end(); ?>
