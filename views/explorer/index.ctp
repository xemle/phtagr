<?php $search->initialize(); ?>

<?php echo $this->element('explorer_menu'); ?>

<?php echo $session->flash(); ?>
<?php echo $navigator->pages() ?>
<?php echo $breadcrumb->breadcrumb($crumbs); ?>

<div class="p-explorer-cells">
<?php
$canWriteTag = count($this->data) ? max(Set::extract('/Media/canWriteTag', $this->data)) : 0;
$cell = 0;
$pos = ($search->getPage(1)-1) * $search->getShow(12) + 1;

foreach ($this->data as $media): ?>

<div class="p-explorer-cell <?php echo ($media['Media']['canWriteTag'] ? 'editable' : '') . " cell" . ($cell % 4); ?>" id="media-<?php echo $media['Media']['id']; ?>">
<h2><?php 
  if (!$search->getUser() || $search->getUser() != $session->read('User.username')) {
    printf(__("%s by %s", true), h($media['Media']['name']), $html->link($media['User']['username'], "/explorer/user/".$media['User']['username']));
  } else {
    echo h($media['Media']['name']);
  }
?></h2>
<?php 
  $size = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  $imageCrumbs = $this->Breadcrumb->replace($crumbs, 'page', $search->getPage());
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', $pos++);
  if ($search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $search->getShow());
  }
  
  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="p-explorer-cell-image"><span></span>';
  echo $html->tag('a',
    $html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1], 
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>

<div class="p-explorer-cell-actions" id="action-<?php echo $media['Media']['id']; ?>">
<ul>
<?php 
  if ($media['Media']['canWriteTag']) {
    $addIcon = $this->Html->image('icons/add.png', array('title' => __('Add to the selection', true), 'alt' => 'add'));
    echo $html->tag('li', 
      $html->link($addIcon, 'javascript:void', array('escape' => false, 'class' => 'add')),
      array('escape' => false));
    $delIcon = $this->Html->image('icons/delete.png', array('title' => __('Remove from the selection', true), 'alt' => 'remove'));
    echo $html->tag('li', 
      $html->link($delIcon, 'javascript:void', array('escape' => false, 'class' => 'del')),
      array('escape' => false));
  }
  if ($media['Media']['canReadOriginal']) {
    foreach ($media['File'] as $file) {
      $diskIcon = $this->Html->image('icons/disk.png', array('title' => sprintf(__('Download file %s', true), $file['file']), 'alt' => 'download'));
      echo $html->tag('li', 
        $html->link($diskIcon, Router::url('/media/file/' . $file['id'] . '/' . $file['file'], true), array('escape' => false, 'class' => 'download')),
        array('escape' => false));
    }
  }
?>
</ul>
</div>

<div class="p-explorer-cell-meta" id="<?php echo 'meta-'.$media['Media']['id']; ?>">
<p id="p-explorer-date">Date: <?php echo $html->link($media['Media']['date'], $imageData->getDateLink($media, '3d')); ?></p>
<?php if (count($media['Tag'])): ?>
  <p id="p-explorer-tags"><?php echo __("Tags") . ': ' . implode(', ', $imageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))); ?></p>
<?php endif; ?>
<?php if (count($media['Category'])): ?>
  <p id="p-explorer-categories"><?php echo __("Categories") . ': ' . implode(', ', $imageData->linkList('/explorer/category', Set::extract('/Category/name', $media))); ?></p>
<?php endif; ?>
<?php if (count($media['Location'])): ?>
  <p id="p-explorer-locations"><?php echo __("Locations") . ': ' . implode(', ', $imageData->linkList('/explorer/location', Set::extract('/Location/name', $media))); ?></p>
<?php endif; ?>
<!--
<table>
  <?php echo $html->tableCells($imageData->metaTable(&$media)); ?>
</table>
-->
</div><!-- meta -->
</div><!-- cell -->

<?php $cell++; endforeach; ?>
</div><!-- cells -->

<?php 
  if ($canWriteTag): ?>
<div class="p-navigator-pages"><div class="sub">
<a id="select-all"><?php __('Select All'); ?></a>
<a id="invert-selection"><?php __('Invert Selection'); ?></a>
</div></div>
<?php endif; ?>

<?php echo $navigator->pages() ?>



<?php 
  echo $this->Html->scriptBlock(<<<'JS'
(function($) {
  $(document).ready(function() {
    $('#content .sub .p-explorer-cell .p-explorer-cell-actions').each(function() {
      var id = $(this).attr('id').split('-')[1];
      var media = $('#media-' + id)
      $(this).find('ul li .add').click(function() {
        $(media).selectMedia();
      });
      $(this).find('ul li .del').hide().click(function() {
        $(media).unselectMedia();
      });
    });
    $('#select-all').click(function() {
      $('#content .sub .p-explorer-cell').selectMedia();
    });
    $('#invert-selection').click(function() {
      $('#content .sub .p-explorer-cell').invertMediaSelection();
    });

    $.fn.activateExplorerMenu = function(id, target) {
      $item = $(id);
      $target = $(target);
      if (!$item || !$target) {
        return;
      }
      $item.siblings('.active').removeClass('active');
      if ($item.hasClass('active')) {
        $item.removeClass('active');
        $target.removeClass('active').hide();
      } else {
        $('#p-explorer-menu-content .active').removeClass('active').hide();
        $item.addClass('active');
        $target.addClass('active').show();
      }
      if ($target[0].nodeName == 'FIELDSET' && $target.hasClass('active')) {
        $('#explorer').children('.submit').show();
      } else {
        $('#explorer').children('.submit').hide();
      }
    }
    $('#p-explorer-all-meta').hide();
    $('#p-explorer-button-all-meta').click(function() {
      $(this).activateExplorerMenu('#p-explorer-button-all-meta', '#p-explorer-all-meta');
    });
    $('#p-explorer-edit-meta').hide();
    $('#p-explorer-button-meta').click(function() {
      $(this).activateExplorerMenu('#p-explorer-button-meta', '#p-explorer-edit-meta');
    });
    $('#p-explorer-edit-access').hide();
    $('#p-explorer-button-access').click(function() {
      $(this).activateExplorerMenu('#p-explorer-button-access', '#p-explorer-edit-access');
    });
    $('#explorer').children('.submit').hide();

    $.fn.placeExplorerMenu = function() {
      $('#p-explorer-menu').css('top', Math.max(0, $('#content').position().top - window.pageYOffset));
    }
    $(window).scroll(function() {
      $(this).placeExplorerMenu();
    });
    $(window).resize(function() {
      $(this).placeExplorerMenu();
    });
    $('#p-explorer-menu-space').css('height', ($('#p-explorer-menu').height()) + 3 + 'px');
  });
  $(window).load(function() {
    $(this).placeExplorerMenu();
  }); 
})(jQuery);
JS
);
?>

