<?php $search->initialize(); ?>

<?php echo $this->element('explorer/menu'); ?>

<?php echo $session->flash(); ?>
<?php echo $breadcrumb->breadcrumb($crumbs); ?>

<?php 
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $('.p-explorer-media-actions').each(function() {
      var id = $(this).attr('id').split('-')[1];
      var media = $('#media-' + id)
      $(this).find('ul li .add').click(function() {
        $(media).selectMedia();
      });
      $(this).find('ul li .del').hide().click(function() {
        $(media).unselectMedia();
      });
      $(this).find('ul li .edit').click(function() {
        $('#dialog').children().remove();
        $('#dialog').load(':BASE_URLexplorer/editmeta/' + id, function() {
          $(this).dialog({
            modal: true, 
            width: 750,
            title: ':EDIT_TITLE',
            buttons: {
              ':SAVE': function() {
                var $form = $('#form-meta-' + id);
                $.post($form.attr('action'), $form.serialize(), function(data) {
                  $('#description-' + id).html(data);
                  $('#description-' + id).find('.tooltip-actions').tooltipAction();
                });
                $(this).dialog("close");
              },
              ':CANCEL': function() {
                $(this).dialog("close");
              }
            }
          });
        });  
      });
    });
    $('#select-all').click(function() {
      $('#content .sub .p-explorer-media').selectMedia();
    });
    $('#invert-selection').click(function() {
      $('#content .sub .p-explorer-media').invertMediaSelection();
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
    $('#explorer').find('.submit input').button();
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
    $.fn.tooltipAction = function() {
      $(this).each(function() {
        var $tooltip = $(this);
        $(this).parent().delayedHover(function() {
          $tooltip.show('fast');
        }, function() {
          $tooltip.hide();
        }, 350);
      })
    };
    $('.tooltip-actions').tooltipAction();
  });
  $(window).load(function() {
    $(this).placeExplorerMenu();
  }); 
})(jQuery);
JS;
  $vars = array(
    'BASE_URL' => Router::url('/', true), 
    'EDIT_TITLE' => __("Edit Meta Data", true),
    'SAVE' => __("Update", true), 
    'CANCEL' => __("Cancel", true));
  foreach ($vars as $name => $value) {
    $script = preg_replace("/:$name/", $value, $script);
  }
  echo $this->Html->scriptBlock($script);
?>

<div class="p-explorer-media-list">
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

<div id="dialog"></div>
