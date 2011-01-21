<h1><?php __("Create Folder"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php printf(__("Location %s", true), $fileList->location($path)); ?></p>

<?php echo $form->create(false, array('action' => 'folder/'.$path)); ?>
<fieldset>
  <?php echo $form->input('Folder.name', array ('label' => __("Folder Name", true))); ?>
</fieldset>
<?php 
  echo $html->tag('ul', 
    $html->tag('li', $form->submit(__('Create', true)), array('escape' => false)),
    array('class' => 'buttons', 'escape' => false));
  echo $form->end();
?>
