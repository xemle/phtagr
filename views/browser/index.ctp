<h1>File Browser</h1>
<?php $session->flash(); ?>

<?php echo $form->create('Browser', array('action' => 'import/'.$path)); ?>

<p>Location <?php echo $fileList->location($path); ?></p>

<?php echo $fileList->table($path, $dirs, $files, array('isInternal' => $isInternal)); ?>

<p>Location <?php echo $fileList->location($path); ?></p>

<?php echo $form->end('Import');?>

<?php if ($isInternal): ?>
<?php echo $html->link("Upload files", 'upload/'.$path); ?> or
<?php echo $html->link("create folder", 'folder/'.$path); ?>. 
<?php endif; ?>
