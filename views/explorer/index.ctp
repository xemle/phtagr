<?php $search->initialize(); ?>

<?php echo $this->element('explorer/menu'); ?>

<?php echo $session->flash(); ?>
<?php echo $breadcrumb->breadcrumb($crumbs); ?>

<?php 
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $.fn.tooltipAction = function() {
      $(this).each(function() {
        var $tooltip = $(this);
        $(this).parent().delayedHover(function() {
          $tooltip.show('fast');
        }, function() {
          $tooltip.hide();
        }, 350);
      });
    };
    $.fn.updateMeta = function(id, crumbs) {
      var $dialog = $('#dialog');
      $.ajax(':BASE_URLexplorer/editmeta/' + id + '/' + crumbs, {
        success: function(data, xhr, status) {
          $dialog.children().remove();
          $dialog.append(data);
          $dialog.dialog({
            modal: true, 
            width: 520,
            title: ':EDIT_TITLE',
            buttons: {
              ':SAVE': function() {
                var $form = $('#form-meta-' + id);
                $.post($form.attr('action'), $form.serialize(), function(data) {
                  $('#media-' + id).html(data);
                  $('#media-' + id).mediaAction();
                });
                $(this).dialog("close");
              },
              ':CANCEL': function() {
                $(this).dialog("close");
              }
            }
          });
        }
      });
    };
    $.fn.updateAcl = function(id, crumbs) {
      var $dialog = $('#dialog');
      $.ajax(':BASE_URLexplorer/editacl/' + id + '/' + crumbs, {
        success: function(data, xhr, status) {
          $dialog.children().remove();
          $dialog.append(data);
          $dialog.dialog({
            modal: true, 
            width: 520,
            title: ':ACL_TITLE',
            buttons: {
              ':SAVE': function() {
                var $form = $('#form-acl-' + id);
                $.post($form.attr('action'), $form.serialize(), function(data) {
                  $('#media-' + id).html(data);
                  $('#media-' + id).mediaAction();
                });
                $(this).dialog("close");
              },
              ':CANCEL': function() {
                $(this).dialog("close");
              }
            }
          });
          $dialog.find('.radioSet').buttonset();
        }
      });
    };
    $.fn.mediaAction = function() {
      $(this).each(function() {
        var id = $(this).attr('id').split('-')[1];
        var media = $('#media-' + id);
        // extract crumb data
        var crumbs = media.find('.p-explorer-media-image a').attr('href').split('/');
        var i = crumbs.length - 1;
        while (i >= 0 && crumbs[i].indexOf(':') > 0) {
          i--;
        }
        crumbs = crumbs.slice(i + 1).join('/');
        $(this).find('ul li .add').click(function() {
          $(media).selectMedia();
        });
        $(this).find('ul li .del').hide().click(function() {
          $(media).unselectMedia();
        });
        $(this).find('ul li .edit').click(function() {
          $(this).updateMeta(id, crumbs);
        });
        $(this).find('ul li .acl').click(function() {
          $(this).updateAcl(id, crumbs);
        });
        $(this).find('.tooltip-actions').tooltipAction();
      });
    };
    $('.p-explorer-media').each(function() {
      $(this).mediaAction();
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
    };
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
    $('#p-explorer-button-slideshow').click(function() {
      var feed = $('#slideshow').attr('href');
      feed += "/quality:high";
      PicLensLite.start({feedUrl: feed});
    });
    $('#explorer').find('.submit input').button();
    $.fn.placeExplorerMenu = function() {
      $('#p-explorer-menu').css('top', Math.max(0, $('#content').position().top - window.pageYOffset));
    };
    $(window).scroll(function() {
      $(this).placeExplorerMenu();
    });
    $(window).resize(function() {
      $(this).placeExplorerMenu();
    });
    $('#p-explorer-menu-space').css('height', ($('#p-explorer-menu').height()) + 3 + 'px');
    $('.tooltip-actions').tooltipAction();
    $('.radioSet').buttonset();
  });
  $(window).load(function() {
    $(this).placeExplorerMenu();
  }); 
})(jQuery);
JS;
  $vars = array(
    'BASE_URL' => Router::url('/', true), 
    'EDIT_TITLE' => __("Edit Meta Data", true),
    'ACL_TITLE' => __("Edit Access Rights", true),
    'SAVE' => __("Update", true), 
    'CANCEL' => __("Cancel", true));
  foreach ($vars as $name => $value) {
    $script = preg_replace("/:$name/", $value, $script);
  }
  echo $this->Javascript->link('/piclenslite/piclens_optimized', false);
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

<div class="p-explorer-media-list">
<?php
$canWriteTag = count($this->data) ? max(Set::extract('/Media/canWriteTag', $this->data)) : 0;
$index = 0;
$pos = ($search->getPage(1)-1) * $search->getShow(12) + 1;

echo '<div class="row">';
foreach ($this->data as $media) {
  $editable = $media['Media']['canWriteTag'] ? 'editable' : '';
  $cell = "cell" . ($index %4);
  echo $html->tag('div', 
    $this->element('explorer/media', array('media' => $media, 'index' => $index, 'pos' => $pos)),
    array('class' => "p-explorer-media $editable $cell", 'id' => 'media-' . $media['Media']['id'], 'escape' => false));
  $index++;
  if ($index % 4 == 0) {
    echo "<div class=\"clear\"> </div></div>\n";
    echo "<div class=\"row\">";
  }
}
echo '<div class="clear"></div></div>';
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
