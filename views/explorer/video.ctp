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
  $size = $imageData->getimagesize($data, OUTPUT_SIZE_VIDEO);
  echo $javascript->link('flashembed.min'); 
?>
<script type="text/javascript">
  window.onload = function() {  
    flashembed("video-<?php echo $data['Image']['id']; ?>", {
      src: '<?php echo Router::url("/flowplayer/FlowPlayerDark.swf"); ?>',
      width: <?php echo $size[0]; ?>, 
      height: <?php echo ($size[1]+28); ?>
    },{config: {  
      autoPlay: true,
      videoFile: '<?php echo Router::url("/files/video/".$data['Image']['id']); ?>',
      initialScale: 'orig',
      loop: false,
      useNativeFullScreen: true
    }} 
  );}
</script>
<div id="video-<?php echo $data['Image']['id']; ?>"></div>

<div class="meta">
<div id="<?php echo 'meta-'.$data['Image']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$data)); ?>
</table>
</div>
</div><!-- meta -->

<?php endif; ?>
