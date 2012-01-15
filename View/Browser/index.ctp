<h1><?php __('File Browser'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create('Browser', array('action' => 'import/'.$path)); ?>

<p><?php printf(__("Location %s", true), $fileList->location($path)); ?>
<?php if ($isInternal) {
  printf(__(" (%s or %s here)", true), 
    $html->link(__("Upload files", true), 'upload/'.$path),
    $html->link(__("create folder", true), 'folder/'.$path));
  } ?>. 

<?php echo $fileList->table($path, $dirs, $files, array('isInternal' => $isInternal)); ?>

<p><?php printf(__("Location %s", true), $fileList->location($path)); ?>
<?php if ($isInternal) {
  printf(__(" (%s or %s here)", true), 
    $html->link(__("Upload files", true), 'upload/'.$path),
    $html->link(__("create folder", true), 'folder/'.$path));
  } ?>. 
</p>

<?php 
  echo $form->submit(__('Import', true));
  echo $form->end();
?>
