<h1><?php __("Media Upload"); ?></h1>

<?php $session->flash(); ?>

<?php if (count($imports)): ?>
<h2><?php __("Your uploaded media"); ?></h2>

<p><?php $count = 1; foreach ($imports as $media): ?>
<?php 
  echo $imageData->mediaLink($media, 'mini') . ' '; 
  if ($count >= 6) {
    break;
  }; 
  $count++;
?>
<?php endforeach; ?></p>
<p><?php echo sprintf(__("See all uploaded media of today %s", true), $html->link(__("here", true), "/explorer/user/" . $session->read('User.username') . "/sort:newest")); ?>
<?php endif; // imports ?>

<?php if ($free > 0): ?>

<?php echo $form->create(false, array('action' => 'quickupload', 'type' => 'file')); ?>
<p><?php __("Upload your photos, videos, GPS logs or ZIP archives here."); ?></p>

<fieldset><legend><?php __("Photos, Videos - ZIP Archives"); ?></legend>
  <?php echo $form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive', true), 'id' => 'FileUpload1')); ?>
  <?php echo $form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive', true), 'id' => 'FileUpload2')); ?>
  <?php echo $form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive', true), 'id' => 'FileUpload3')); ?>
  <?php echo $form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive', true), 'id' => 'FileUpload4')); ?>
  <?php echo $form->input('File.upload][', array('type' => 'file', 'label' => __('File or archive', true), 'id' => 'FileUpload5')); ?>
</fieldset>
<p><?php printf(__("You can upload maximal %s and %s at once. ZIP archives are extracted automatically.", true), $number->toReadableSize($free), $number->toReadableSize($max)); ?></p>

<?php echo $form->end(__("Upload", true)); ?>

<?php else: ?>
<p class="info"><?php __("You cannot upload files now. Your upload quota is exceeded."); ?></p>
<?php endif; ?>
