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

<p><?php echo __("Upload your photos, videos, GPS logs or ZIP archives here."); ?></p>
<p><?php echo __("You can upload maximal %s and %s at once. ZIP archives are extracted automatically.", $this->Number->toReadableSize($free), $this->Number->toReadableSize($max)); ?></p>
<?php echo $this->Plupload->upload($currentUser); ?>

<?php else: ?>
<p class="info"><?php echo __("You cannot upload files now. Your upload quota is exceeded."); ?></p>
<?php endif; ?>
