<?php echo $this->Session->flash(); ?>

<div id="p-media-preview">
<div class="image">
<span></span>
<?php
  if (($this->request->data['Media']['type'] & MEDIA_TYPE_VIDEO) > 0) {
    echo $this->Flowplayer->video($this->request->data);
  } else {
    //$size = $this->ImageData->getimagesize($this->request->data, OUTPUT_SIZE_PREVIEW);
    $size = $this->ImageData->getimagesize($this->request->data, 960);
    $src = Router::url("/media/preview/".$this->request->data['Media']['id']);
    $img = $this->Html->tag('img', null, array('src' => $src, 'width' => $size[0], 'height' => $size[1], 'alt' => $this->request->data['Media']['name']));
    if ($this->Navigator->hasNextMedia()) {
      echo $this->Html->link($img, $this->Navigator->getNextMediaUrl(), array('escape' => false));
    } else {
      echo $img;
    }
  }
?>
</div>
<div class="navigator">
<div class="up"><div class="sub"><?php echo $this->Navigator->up(); ?></div></div>
<?php if ($this->Navigator->hasPrevMedia()): ?>
<div class="prev"><div class="sub"><?php echo $this->Navigator->prevMedia(); ?></div></div>
<?php endif; ?>
<?php if ($this->Navigator->hasNextMedia()): ?>
<div class="next"><div class="sub"><?php echo $this->Navigator->nextMedia(); ?></div></div>
<?php endif; ?>
</div>
</div>

<div id="image-tabs">
<?php
  $items = array(__("General"), __("Media Details"));
  if ($this->Map->hasMediaGeo($this->request->data)) {
    $items['map'] = __("Map");
  }
  if ($this->request->data['Media']['canWriteTag']) {
    $items['edit'] = __("Edit");
  }
  if ($this->request->data['Media']['canWriteAcl']) {
    $items['acl'] = __("Access Right");
  }
  echo $this->Tab->menu($items);
?>
<?php echo $this->Tab->open(0); ?>
<div class="meta">
<div id="meta-<?php echo $this->request->data['Media']['id']; ?>">
<table class="bare">
  <?php echo $this->Html->tableCells($this->ImageData->metaTable($this->request->data)); ?>
</table>
</div>
</div><!-- meta -->
<?php echo $this->Tab->close(); ?>

<?php echo $this->Tab->open(1); ?>
<div class="meta">
<table class="bare">
<?php
  $cells = array();
  $cells[] = array(__("User"), $this->Html->link($this->request->data['User']['username'], '/explorer/user/'.$this->request->data['User']['username']));
  if ($this->request->data['Media']['isOwner']) {
    $files = array();
    foreach ($this->request->data['File'] as $file) {
      $link = $this->ImageData->getPathLink($file);
      $files[] = $this->Html->link($file['file'], $link).' ('.$this->Number->toReadableSize($file['size']).')';
    }
    $cells[] = array(__("File(s)"), implode(', ', $files));
  }
  $folders = $this->ImageData->getFolderLinks($this->request->data);
  if ($folders) {
    $cells[] = array(__("Folder"), implode(' / ', $folders));
  }
  $cells[] = array(__("View Count"), $this->request->data['Media']['clicks']);
  $cells[] = array(__("Created"), $this->Time->timeAgoInWords($this->request->data['Media']['created']));
  $cells[] = array(__("Last modified"), $this->Time->timeAgoInWords($this->request->data['Media']['modified']));
  $cells[] = array(__("Size"), $this->request->data['Media']['width'].'px * '.$this->request->data['Media']['height'].'px');

  if ($this->request->data['Media']['model']) {
    $cells[] = array(__("Model"), $this->request->data['Media']['model']);
  }
  if ($this->request->data['Media']['duration'] > 0) {
    $cells[] = array(__("Duration"), $this->request->data['Media']['duration'].'s');
  } else {
    if ($this->request->data['Media']['aperture'] > 0) {
      $cells[] = array(__("Aperture"), $this->request->data['Media']['aperture']);
    }
    if ($this->request->data['Media']['shutter'] > 0) {
      $cells[] = array(__("Shutter"), $this->ImageData->niceShutter($this->request->data['Media']['shutter']));
    }
    if ($this->request->data['Media']['iso'] > 0) {
      $cells[] = array(__("ISO"), $this->request->data['Media']['iso']);
    }
  }
  echo $this->Html->tableCells($cells);
?>
</table>
</div>
<?php echo $this->Tab->close(); ?>
<?php
  if ($this->Map->hasMediaGeo($this->request->data)) {
    echo $this->Tab->open('map');
    echo $this->Map->container();
    echo $this->Map->script();
    echo $this->Tab->close();
  }
  if ($this->request->data['Media']['canWriteTag']) {
    echo $this->Tab->open('edit');
    echo $this->Form->create(null, array('url' => 'update/'.$this->request->data['Media']['id'].'/'.join('/', $crumbs), 'id' => 'edit'));
    echo "<fieldset>";
    echo View::element('single_meta_form');
    echo "</fieldset>";
    echo $this->Form->end(__('Save'));
    echo $this->Tab->close();
  }
  if ($this->request->data['Media']['canWriteAcl']) {
    echo $this->Tab->open('acl');
    echo $this->Form->create(null, array('url' => 'updateAcl/'.$this->request->data['Media']['id'].'/'.join('/', $crumbs), 'id' => 'acl'));
    echo "<fieldset>";
    echo View::element('single_acl_form');
    echo "</fieldset>";
    echo $this->Form->end(__('Save'));
    echo $this->Tab->close();
  }
?>
</div><!-- tabs -->

<?php
  $mediaId = $this->request->data['Media']['id'];
  $lat = $this->request->data['Media']['latitude'] ? $this->request->data['Media']['latitude'] : 0;
  $long = $this->request->data['Media']['longitude'] ? $this->request->data['Media']['longitude'] : 0;
  $script = <<<SCRIPT
var map = null;
(function($) {
$.fn.resizeImageHeight = function(size) {
  var image = $(this);
  var w = image.attr('width');
  var h = image.attr('height');
  if (0 >= Math.min(w, h) || size > h) {
    return;
  }
  image.attr('width', size * (w / h));
  image.attr('height', size);
};
$.fn.resizeImage = function(size) {
  var image = $(this);
  var w = image.attr('width');
  var h = image.attr('height');
  if (size < 100 || 0 >= Math.min(w, h) || size > Math.max(w, h)) {
    return;
  }
  if (w > h) {
    h = size * (h / w);
    w = size;
  } else {
    w = size * (w / h);
    h = size;
  }
  image.attr('width', w);
  image.attr('height', h);
};
$(document).ready(function() {
  media = $('#p-media-preview');
  if (media) {
    var top = media.position().top;
    var size = $(window).height() - top - 10;
    media.find('img').resizeImageHeight(size);
  }
  $("#image-tabs").tabs({
    show: function(event, ui) {
      if (ui.panel.id == 'tab-map') {
        if ($('#map').children().length == 0) {
          $('#mapbox').show();
	  mapOptions.center = {
	      lon: $long,
	      lat: $lat
	  };
          map = new phMap(mapOptions);
          map.addMarker($mediaId, $lat, $long);
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
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

<?php echo View::element('comment'); ?>
