<h1><?php echo $this->data['Medium']['name'] ?></h1>
<?php $session->flash(); ?>


<div class="paginator"><div class="subpaginator">
<?php
echo $query->prevMedium().' '.$query->up().' '.$query->nextMedium();
?>
</div></div>

<?php
  $withMap = false;
  if (isset($this->data['Medium']['longitude']) && isset($this->data['Medium']['latitude']) &&
    isset($mapKey)) {
    $withMap = true;
    echo $map->loadScripts($mapKey);
    echo $map->script();
  }
?>

<?php 
  $size = $imageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
  echo "<img src=\"".Router::url("/media/preview/".$this->data['Medium']['id'])."\" $size[3] alt=\"{$this->data['Medium']['name']}\"/>"; ?>

<div class="meta">
<div id="<?php echo 'meta-'.$this->data['Medium']['id']; ?>">
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
