<h1><?php echo __('File Browser'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('Browser', array('action' => 'import/'.$path)); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?>
<?php if ($isInternal) {
  echo __(" (%s or %s here)",
    $this->Html->link(__("Upload files"), 'upload/'.$path),
    $this->Html->link(__("create folder"), 'folder/'.$path));
  } ?>.

<?php echo $this->FileList->table($path, $dirs, $files, array('isInternal' => $isInternal)); ?>

<p><?php echo __("Location %s", $this->FileList->location($path)); ?>
<?php if ($isInternal) {
  echo __(" (%s or %s here)",
    $this->Html->link(__("Upload files"), 'upload/'.$path),
    $this->Html->link(__("create folder"), 'folder/'.$path));
  } ?>.
</p>

<?php
  echo $this->Form->input('Browser.options.recursive', array('type' => 'checkbox', 'label' => __('Recursive')));
  echo $this->Form->input('Browser.options.forceReadMeta', array('type' => 'checkbox', 'label' => __('Only for existing media - Force reread metadata')));
?>

<p>
<?php
   $ExtSelect = array(
    'any' => __('any'),
    'xmp' => __('xmp (sidecar)'),
    'jpg' => __('jpg'),
    'avi' => __('avi'));
   $ExtSelected = array('any');
   echo $this->Html->tag('div',
           $this->Form->input('Browser.options.extToRead', array('type' => 'select', 'options' => $ExtSelect,'multiple' => 'checkbox', 'selected' => $ExtSelected, 'label' => __("Select extensions to be imported:"))),
           array('class' => 'submit-list'));
?></p>

<?php
  echo $this->Html->tag('div',
    $this->Form->submit(__('Import'), array('div' => false, 'name' => 'import', 'value' => 'import'))
    . $this->Form->submit(__('Unlink'), array('div' => false, 'name' => 'unlink', 'value' => 'unlink')),
    array('class' => 'submit-list'));
  echo $this->Form->end();
?>
