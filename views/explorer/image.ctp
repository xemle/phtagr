<?php if (!isset($data)): ?>
<div class="info">No Image could be found</div>
<?php else: ?>
<h1><?php echo $data['Image']['name'] ?></h1>
<?php $session->flash(); ?>

<div class="navigator">
<?php
echo $search->prevImage().' '.$search->up().' '.$search->nextImage();
?>
</div>

<?php 
  $size = $imageData->getimagesize($data, OUTPUT_SIZE_PREVIEW);
  echo "<img src=\"".Router::url("/files/preview/".$data['Image']['id'])."\" $size[3] alt=\"{$data['Image']['name']}\"/>"; ?>

<div class="meta">
<div id="<?php echo 'meta-'.$data['Image']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$data)); ?>
</table>
</div>
</div><!-- meta -->

<?php endif; ?>
