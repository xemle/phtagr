<h1>Create Folder</h1>
<?php $session->flash(); ?>

<p>Create new folder at: <?php echo $html->link($path, 'index/'.$path); ?></p>

<?php echo $form->create(false, array('action' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $form->input('Folder.name', array ('label' => "Folder Name")); ?>
</fieldset>
<?php echo $form->submit("Create"); ?>
<?php echo $form->end(); ?>
