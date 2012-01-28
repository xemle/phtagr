<?php echo $this->Session->flash(); ?>

<div id="p-media-preview">
<div class="image">
<span></span>
<?php 
  if (($this->data['Media']['type'] & MEDIA_TYPE_VIDEO) > 0) {
    echo $flowplayer->video($this->data);
  } else {
    //$size = $this->ImageData->getimagesize($this->data, OUTPUT_SIZE_PREVIEW);
    $size = $this->ImageData->getimagesize($this->data, 960);
    $src = Router::url("/media/preview/".$this->data['Media']['id']);
    $img = $this->Html->tag('img', null, array('src' => $src, 'width' => $size[0], 'height' => $size[1], 'alt' => $this->data['Media']['name']));
    if ($navigator->hasNextMedia()) {
      echo $this->Html->link($img, $navigator->getNextMediaUrl(), array('escape' => false));
    } else {
      echo $img;
    }
  }
?>
</div>
<div class="navigator">
<div class="up"><div class="sub"><?php echo $navigator->up(); ?></div></div>
<?php if ($navigator->hasPrevMedia()): ?>
<div class="prev"><div class="sub"><?php echo $navigator->prevMedia(); ?></div></div>
<?php endif; ?>
<?php if ($navigator->hasNextMedia()): ?>
<div class="next"><div class="sub"><?php echo $navigator->nextMedia(); ?></div></div>
<?php endif; ?>
</div>
</div>

<div id="image-tabs">
<?php
  $items = array(__("General"), __("Media Details"));
  if ($map->hasApi() && $map->hasMediaGeo($this->data)) {
    $items['map'] = __("Map");
  }
  if ($this->data['Media']['canWriteTag']) {
    $items['edit'] = __("Edit");
  }
  if ($this->data['Media']['canWriteAcl']) {
    $items['acl'] = __("Access Right");
  }
  echo $tab->menu($items);
?>
<?php echo $tab->open(0); ?>
<div class="meta">
<div id="meta-<?php echo $this->data['Media']['id']; ?>">
<table class="bare"> 
  <?php echo $this->Html->tableCells($this->ImageData->metaTable(&$this->data)); ?>
</table>
</div>
</div><!-- meta -->
<?php echo $tab->close(); ?>

<?php echo $tab->open(1); ?>
<div class="meta">
<table class="bare"> 
<?php 
  $cells = array();
  $cells[] = array(__("User"), $this->Html->link($this->data['User']['username'], '/explorer/user/'.$this->data['User']['username']));
  if ($this->data['Media']['isOwner']) {
    $files = array();
    foreach ($this->data['File'] as $file) {
      $link = $this->ImageData->getPathLink($file);
      $files[] = $this->Html->link($file['file'], $link).' ('.$this->Number->toReadableSize($file['size']).')';
    }
    $cells[] = array(__("File(s)"), implode(', ', $files));
  }
  $folders = $this->ImageData->getFolderLinks($this->data);
  if ($folders) {
    $cells[] = array(__("Folder"), implode(' / ', $folders));
  }
  $cells[] = array(__("View Count"), $this->data['Media']['clicks']);
  $cells[] = array(__("Created"), $time->relativeTime($this->data['Media']['created']));
  $cells[] = array(__("Last modified"), $time->relativeTime($this->data['Media']['modified']));
  $cells[] = array(__("Size"), $this->data['Media']['width'].'px * '.$this->data['Media']['height'].'px');

  if ($this->data['Media']['model']) {
    $cells[] = array(__("Model"), $this->data['Media']['model']);
  }
  if ($this->data['Media']['duration'] > 0) {
    $cells[] = array(__("Duration"), $this->data['Media']['duration'].'s');
  } else {
    if ($this->data['Media']['aperture'] > 0) {
      $cells[] = array(__("Aperture"), $this->data['Media']['aperture']);
    }
    if ($this->data['Media']['shutter'] > 0) {
      $cells[] = array(__("Shutter"), $this->ImageData->niceShutter($this->data['Media']['shutter']));
    }
    if ($this->data['Media']['iso'] > 0) {
      $cells[] = array(__("ISO"), $this->data['Media']['iso']);
    }
  }
  echo $this->Html->tableCells($cells);
?>
</table>
</div>
<?php echo $tab->close(); ?>
<?php 
  if ($map->hasApi() && $map->hasMediaGeo($this->data)) {
    echo $tab->open('map');
    echo $map->container();
    echo $map->script();
    echo $tab->close(); 
  }
  if ($this->data['Media']['canWriteTag']) {
    echo $tab->open('edit');
    echo $this->Form->create(null, array('url' => 'update/'.$this->data['Media']['id'].'/'.join('/', $crumbs), 'id' => 'edit'));
    echo "<fieldset>";
    echo View::element('single_meta_form');
    echo "</fieldset>";
    echo $this->Form->end(__('Save'));
    echo $tab->close(); 
  }
  if ($this->data['Media']['canWriteAcl']) {
    echo $tab->open('acl');
    echo $this->Form->create(null, array('url' => 'updateAcl/'.$this->data['Media']['id'].'/'.join('/', $crumbs), 'id' => 'acl'));
    echo "<fieldset>";
    echo View::element('single_acl_form');
    echo "</fieldset>";
    echo $this->Form->end(__('Save'));
    echo $tab->close(); 
  }
?>
</div><!-- tabs -->

<?php 
  $script = <<<'JS'
(function($) {
$.fn.resizeImageHeight = function(size) {
  var $image = $(this);
  var w = $image.attr('width');
  var h = $image.attr('height');
  if (0 >= Math.min(w, h) || size > h) {
    return;
  }
  $image.attr('width', size * (w / h));
  $image.attr('height', size); 
};
$.fn.resizeImage = function(size) {
  var $image = $(this);
  var w = $image.attr('width');
  var h = $image.attr('height');
  if (0 >= Math.min(w, h) || size > Math.max(w, h)) {
    return;
  }
  if (w > h) {
    h = size * (h / w);
    w = size;
  } else {
    w = size * (w / h);
    h = size;
  }
  $image.attr('width', w);
  $image.attr('height', h); 
};
$(document).ready(function() {
  $media = $('#p-media-preview');
  if ($media) {
    var top = $media.position().top;
    var size = window.innerHeight - top - 10;
    $media.find('img').resizeImageHeight(size);
  }
  $("#image-tabs").tabs({
    show: function(event, ui) {
      if (ui.panel.id == 'tab-map') {
        if ($('#map').children().length == 0) {
          $('#mapbox').show();
          loadMap(:ID, :LATITUDE, :LONGITUDE); 
        }
      }
      return true;
    }
  });
  $("#comment-add :submit").button();
  $("#edit :submit").button();
  $("#acl :submit").button();
});
})(jQuery);
JS;
  $vars = array(
    'ID' => $this->data['Media']['id'],
    'LATITUDE' => ($this->data['Media']['latitude'] ? $this->data['Media']['latitude'] : 0),
    'LONGITUDE' => ($this->data['Media']['longitude'] ? $this->data['Media']['longitude'] : 0));
  foreach ($vars as $name => $value) {
    $script = preg_replace("/:$name/", $value, $script);
  }
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

<?php echo View::element('comment'); ?>
