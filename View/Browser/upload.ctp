<h1><?php echo __("File Upload"); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php if ($free > 0): ?>
<p><?php echo __("You upload files to folder %s", $this->FileList->location($path)); ?></p>
<p><?php echo __("You can upload maximal %s and %s at once", $this->Number->toReadableSize($free), $this->Number->toReadableSize($max)); ?></p>

<?php echo $this->Form->create(false, array('url' => 'upload/'.$path, 'type' => 'file')); ?>
<fieldset><legend><?php echo __("Upload files"); ?></legend>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload1')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload2')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload3')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload4')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload5')); ?>
  <?php echo $this->Form->input('File.extract', array('label' => __('Extract ZIP archive'), 'checked' => true, 'type' => 'checkbox')); ?>
</fieldset>

<?php
  echo $this->Html->tag('ul',
    $this->Html->tag('li', $this->Form->submit(__('Upload')), array('escape' => false)),
    array('class' => 'buttons', 'escape' => false));
  echo $this->Form->end();
?>

<?php else: ?>
<p class="info"><?php echo __("You cannot upload files now. Your upload quota is exceeded."); ?></p>
<?php endif; ?>
