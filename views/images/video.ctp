<h1><?php echo $this->data['Media']['name'] ?></h1>
<?php $session->flash(); ?>

<div class="paginator"><div class="subpaginator">
<?php
echo $navigator->prevMedia().' '.$navigator->up().' '.$navigator->nextMedia();
?>
</div></div>

<?php
  $withMap = false;
  if (isset($this->data['Media']['longitude']) && isset($this->data['Media']['latitude']) &&
    isset($mapKey)) {
    $withMap = true;
    echo $map->loadScripts($mapKey);
    echo $map->script();
  }
?>

<?php echo $flowplayer->video($this->data); ?>

<div class="meta">
<div id="<?php echo 'meta-'.$this->data['Media']['id']; ?>">
<table> 
  <?php echo $html->tableCells($imageData->metaTable(&$this->data, $withMap)); ?>
</table>
</div>
</div><!-- meta -->

<?php if ($withMap) {
  echo $map->container();
}
?>

<?php echo View::element('comment'); ?>
