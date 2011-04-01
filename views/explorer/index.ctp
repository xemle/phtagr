<?php $search->initialize(); ?>

<?php echo $this->element('explorer/menu'); ?>

<?php echo $session->flash(); ?>
<?php echo $breadcrumb->breadcrumb($crumbs); ?>

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
        $target.addClass('active').slideDown('fast');
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
    $('.tooltip-actions').each(function() {
      var $tooltip = $(this);
      $(this).parent().delayedHover(function() {
        $tooltip.show('fast');
      }, function() {
        $tooltip.hide();
      }, 350);
    });
  });
  $(window).load(function() {
    $(this).placeExplorerMenu();
  }); 
})(jQuery);
JS
);
?>

<div class="p-explorer-cells">
<?php
$canWriteTag = count($this->data) ? max(Set::extract('/Media/canWriteTag', $this->data)) : 0;
$index = 0;
$pos = ($search->getPage(1)-1) * $search->getShow(12) + 1;

foreach ($this->data as $media) {
  echo $this->element('explorer/media', array('media' => $media, 'index' => $index, 'pos' => $pos));
  $index++;
}
?>
</div><!-- cells -->

<?php 
  if ($canWriteTag): ?>
<div class="p-navigator-pages"><div class="sub">
<a id="select-all"><?php __('Select All'); ?></a>
<a id="invert-selection"><?php __('Invert Selection'); ?></a>
</div></div>
<?php endif; ?>

<?php echo $navigator->pages() ?>
