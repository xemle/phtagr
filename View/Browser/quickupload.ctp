<h1><?php echo __("Media Upload"); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php if (count($imports)): ?>
<h2><?php echo __("Your uploaded media"); ?></h2>

<p><?php $count = 1; foreach ($imports as $media): ?>
<?php 
  echo $this->ImageData->mediaLink($media, 'mini') . ' '; 
  if ($count >= 6) {
    break;
  }; 
  $count++;
?>
<?php endforeach; ?></p>
<p><?php echo __("See all uploaded media of today %s", $this->Html->link(__("here"), "/explorer/user/" . $this->Session->read('User.username') . "/folder/" . date('Y/Y-m-d', time()))); ?>
<?php endif; // imports ?>

<?php if ($free > 0): ?>

<?php echo $this->Form->create(false, array('action' => 'quickupload', 'type' => 'file')); ?>
<p><?php echo __("Upload your photos, videos, GPS logs or ZIP archives here."); ?></p>

<fieldset><legend><?php echo __("Photos, Videos - ZIP Archives"); ?></legend>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload1')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload2')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload3')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload4')); ?>
  <?php echo $this->Form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive'), 'id' => 'FileUpload5')); ?>
</fieldset>

<?php echo $this->Form->end(__("Upload")); ?>
<?php 
    $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $('input[type=submit]').button();
  });
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
<p><?php echo __("You can upload maximal %s and %s at once. ZIP archives are extracted automatically.", $this->Number->toReadableSize($free), $this->Number->toReadableSize($max)); ?></p>
<?php else: ?>
<p class="info"><?php echo __("You cannot upload files now. Your upload quota is exceeded."); ?></p>
<?php endif; ?>
