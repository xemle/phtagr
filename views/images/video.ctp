<h1><?php echo $this->data['Media']['name'] ?></h1>
<?php $session->flash(); ?>

<div class="paginator"><div class="subpaginator">
<?php
echo $query->prevMedia().' '.$query->up().' '.$query->nextMedia();
?>
</div></div>

<?php 
  $size = $imageData->getimagesize($this->data, OUTPUT_SIZE_VIDEO);
  echo $javascript->link('flashembed.min'); 
?>
<script type="text/javascript">
  window.onload = function() {  
    flashembed("video-<?php echo $this->data['Media']['id']; ?>", {
      src: '<?php echo Router::url("/flowplayer/FlowPlayerDark.swf", true); ?>',
      width: <?php echo $size[0]; ?>, 
      height: <?php echo ($size[1]+28); ?>
    },{config: {  
      autoPlay: true,
      videoFile: '<?php echo Router::url("/media/video/".$this->data['Media']['id'], true); ?>',
      initialScale: 'orig',
      loop: false,
      useNativeFullScreen: true
    }} 
  );}
</script>
<div id="video-<?php echo $this->data['Media']['id']; ?>"></div>

<div class="meta">
<div id="<?php echo 'meta-'.$this->data['Media']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$this->data)); ?>
</table>
</div>
</div><!-- meta -->

<?php //echo View::element('comment'); ?>
