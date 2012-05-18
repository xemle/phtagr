<?php $this->Search->initialize(); ?>

<?php echo $this->element('Explorer/menu'); ?>

<?php echo $this->Session->flash(); ?>
<?php echo $this->Breadcrumb->breadcrumb($crumbs); ?>

<?php
  $baseUrl = Router::url('/', true);
  $editTitleText = __("Edit Meta Data");
  $aclTitleText = __("Edit Access Rights");
  $saveText = __("Update");
  $cancelText = __("Cancel");
  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $.fn.tooltipAction = function() {
      $(this).each(function() {
        var tooltip = $(this);
        $(this).parent().delayedHover(function() {
          tooltip.show('fast');
        }, function() {
          tooltip.hide();
        }, 350);
      });
    };
    $.fn.updateMeta = function(id, crumbs) {
      var dialog = $('#dialog');
      $.ajax('{$baseUrl}explorer/editmeta/' + id + '/' + crumbs, {
        success: function(data, xhr, status) {
          dialog.children().remove();
          dialog.append(data);
          dialog.dialog({
            modal: true,
            width: 520,
            title: '$editTitleText',
            buttons: {
              '$saveText': function() {
                var form = $('#form-meta-' + id);
                $.post(form.attr('action'), form.serialize(), function(data) {
                  $('#media-' + id).html(data);
                  $('#media-' + id).mediaAction();
                });
                $(this).dialog("close");
              },
              '$cancelText': function() {
                $(this).dialog("close");
              }
            }
          });
        }
      });
    };
    $.fn.updateAcl = function(id, crumbs) {
      var dialog = $('#dialog');
      $.ajax('{$baseUrl}explorer/editacl/' + id + '/' + crumbs, {
        success: function(data, xhr, status) {
          dialog.children().remove();
          dialog.append(data);
          dialog.dialog({
            modal: true,
            width: 520,
            title: '$aclTitleText',
            buttons: {
              '$saveText': function() {
                var form = $('#form-acl-' + id);
                $.post(form.attr('action'), form.serialize(), function(data) {
                  $('#media-' + id).html(data);
                  $('#media-' + id).mediaAction();
                });
                $(this).dialog("close");
              },
              '$cancelText': function() {
                $(this).dialog("close");
              }
            }
          });
          dialog.find('.radioSet').buttonset();
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
      var item = $(id);
      var target = $(target);
      if (!item || !target) {
        return;
      }
      item.siblings('.active').removeClass('active');
      if (item.hasClass('active')) {
        item.removeClass('active');
        target.removeClass('active').hide();
      } else {
        $('#p-explorer-menu-content .active').removeClass('active').hide();
        item.addClass('active');
        target.addClass('active').slideDown('fast');
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
    $('#p-explorer-more').hide();
    $('#p-explorer-button-more').click(function() {
      $(this).activateExplorerMenu('#p-explorer-button-more', '#p-explorer-more');
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
    /**
     * Triggers a zip download via a fake form submition
     */
    $.fn.triggerZipDownload = function(url, mediaIds) {
      if (!mediaIds.length) {
        return;
      }
      var input = '<input type="hidden" name="data[Media][ids]" value="' + mediaIds + '"/>';
      var fakeForm = '<form action="' + url + '" method="post">' + input + '</form>';
      $(fakeForm).appendTo('body').submit().remove();
    };
    $('#p-explorer-download-original').click(function() {
      var url = '{$baseUrl}media/zip/original';
      var mediaIds = $('#MediaIds').val();
      $(this).triggerZipDownload(url, mediaIds);
    });
    $('#p-explorer-download-high').click(function() {
      var url = '{$baseUrl}media/zip/high';
      var mediaIds = $('#MediaIds').val();
      $(this).triggerZipDownload(url, mediaIds);
    });
    $('#p-explorer-download-preview').click(function() {
      var url = '{$baseUrl}media/zip/preview';
      var mediaIds = $('#MediaIds').val();
      $(this).triggerZipDownload(url, mediaIds);
    });
    $('.tooltip-actions').tooltipAction();
    $('.radioSet').buttonset();
  });
  $(window).load(function() {
    $(this).placeExplorerMenu();
  });
})(jQuery);
SCRIPT;
  echo $this->Html->script('/piclenslite/piclens_optimized');
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

<div class="p-explorer-media-list">
<?php
$canWriteTag = count($this->request->data) ? max(Set::extract('/Media/canWriteTag', $this->request->data)) : 0;
$index = 0;
$pos = ($this->Search->getPage(1)-1) * $this->Search->getShow(12) + 1;

echo '<div class="row">';
foreach ($this->request->data as $media) {
  $editable = $media['Media']['canWriteTag'] ? 'editable' : '';
  $cell = "cell" . ($index %4);
  echo $this->Html->tag('div',
    $this->element('Explorer/media', array('media' => $media, 'index' => $index, 'pos' => $pos)),
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
<a id="select-all"><?php echo __('Select All'); ?></a>
<a id="invert-selection"><?php echo __('Invert Selection'); ?></a>
</div></div>
<?php endif; ?>

<?php echo $this->Navigator->pages() ?>

<div id="dialog"></div>
