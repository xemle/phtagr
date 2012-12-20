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

<fieldset><legend><?php echo __("Import Options") ?></legend>
<?php
  echo $this->Form->input('Browser.options.recursive', array('type' => 'checkbox', 'label' => __('Recursive')));
  echo $this->Form->input('Browser.options.forceReadMeta', array('type' => 'checkbox', 'label' => __('Reread metadata for exisiting files')));
?>
</fieldset>

<fieldset><legend><?php echo __("File Filter") ?></legend>
<?php
   $options = array(
    'any' => __('Any'),
    'xmp' => __('XMP (sidecar)'),
    'jpg' => __('JPG'),
    'avi' => __('AVI'));
   $selected = 'any';
   echo $this->Form->input('Browser.options.extensions', array('type' => 'select', 'options' => $options, 'multiple' => 'checkbox', 'selected' => $selected, 'label' => __("Select extensions to be imported:")));
?>
</fieldset>

<?php
  echo $this->Html->tag('div',
    $this->Form->submit(__('Import'), array('div' => false, 'name' => 'import', 'value' => 'import'))
    . $this->Form->submit(__('Unlink'), array('div' => false, 'name' => 'unlink', 'value' => 'unlink')),
    array('class' => 'submit-list'));
  echo $this->Form->end();
?>
