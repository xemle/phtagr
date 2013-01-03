<?php $this->Search->initialize(); ?>

<?php echo $this->element('Explorer/menu'); ?>

<?php echo $this->Session->flash(); ?>
<?php echo $this->Breadcrumb->breadcrumb($crumbs); ?>

<?php
  $baseUrl = Router::url('/', true);
  $editTitleText = h(__("Edit Meta Data"));
  $aclTitleText = h(__("Edit Access Rights"));
  $saveText = h(__("Update"));
  $cancelText = h(__("Cancel"));
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
    $.fn.extractCrumbs = function(link) {
      if (!link) {
        return false;
      }
      var crumbs = link.split('/');
      var i = crumbs.length - 1;
      while (i >= 0 && crumbs[i].indexOf(':') > 0) {
        i--;
      }
      return crumbs.slice(i + 1).join('/');
    };
    $.fn.mediaAction = function() {
      var data = $('body').data('phtagr');
      if (!data) {
        $('body').data('phtagr', {lastSelected:null, ids: []});
        data = $('body').data('phtagr');
      }
      $(this).each(function() {
        var id = $(this).attr('id').split('-')[1];
        data.ids.push(id);
        var media = $('#media-' + id);
        // extract crumb data
        var crumbs = $.fn.extractCrumbs(media.find('.preview a').attr('href'));
        $(this).find('.preview').click(function(event) {
          if (event.ctrlKey) {
            $(media).toggleMedia();
            event.preventDefault();
          }
          if (data.lastSelected && event.ctrlKey && event.shiftKey) {
            var last = $('#media-' + data.lastSelected);
            var isSelected = media.hasClass('selected');
            if (last.hasClass('selected') == isSelected) {
              var start = data.ids.indexOf(id);
              var end = data.ids.indexOf(data.lastSelected);
              if (start > end) {
                var tmp = end; end = start; start = tmp;
              }
              start++;
              while (start < end) {
                if (isSelected) {
                  $('#media-' + data.ids[start++]).selectMedia();
                } else {
                  $('#media-' + data.ids[start++]).unselectMedia();
                }
              }
            }
          }
          data.lastSelected = id;
        });
        $(this).find('.preview a').click(function(event) {
          if (event.ctrlKey) {
            event.preventDefault();
          }
        });
        $(this).find('.actions .add a').click(function() {
          $(media).selectMedia();
        });
        $(this).find('.actions .del').hide();
        $(this).find('.actions .del a').click(function() {
          $(media).unselectMedia();
        });
        $(this).find('.actions .edit a').click(function() {
          $(this).updateMeta(id, crumbs);
        });
        $(this).find('.actions .acl a').click(function() {
          $(this).updateAcl(id, crumbs);
        });
        $(this).find('.tooltip-actions').tooltipAction();
      });
    };
    $('#p-explorer-media-list .cell').each(function() {
      $(this).mediaAction();
    });
    $('#select-all').click(function() {
      $('#p-explorer-media-list .cell').selectMedia();
    });
    $('#invert-selection').click(function() {
      $('#p-explorer-media-list .cell').invertMediaSelection();
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
      var firstMediaId = mediaIds.split(',').pop();
      var crumbs = $.fn.extractCrumbs($('.p-breadcrumb-crumb').last().find('a').first().attr('href'));
      if (crumbs) {
        url += '/' + crumbs;
      }
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
    $('#p-explorer-selection-remove').click(function() {
      var url = '{$baseUrl}explorer/selection/unlink';
      var mediaIds = $('#MediaIds').val();
      $(this).triggerZipDownload(url, mediaIds);
    });
    $('#p-explorer-selection-delete-cache').click(function() {
      var url = '{$baseUrl}explorer/selection/deleteCache';
      var mediaIds = $('#MediaIds').val();
      $(this).triggerZipDownload(url, mediaIds);
    });
    $('#p-explorer-selection-clear-sync').click(function() {
      var url = '{$baseUrl}explorer/selection/sync';
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
<?php
  $view = $this->Search->getView();
  if ($view == 'small') {
    $class = "p-list-small";
    $element = "Explorer/media_small";
    $columns = 8;
  } else if ($view == 'compact' ) {
    $class = "p-list-compact";
    $element = "Explorer/media_compact";
    $columns = 4;
  } else {
    $class = "p-list-default";
    $element = "Explorer/media";
    $columns = 4;
  }
?>
<div id="p-explorer-media-list" class="<?php echo $class; ?>">
<?php
$canWriteTag = count($this->request->data) ? max(Set::extract('/Media/canWriteTag', $this->request->data)) : 0;
$index = 0;
$pos = ($this->Search->getPage(1)-1) * $this->Search->getShow(12) + 1;

echo '<div class="row">';
foreach ($this->request->data as $media) {
  $editable = $media['Media']['canWriteTag'] ? 'editable' : '';
  $cell = (($index + 1) % $columns) ? "cell" : "cell cell-right";
  echo $this->Html->tag('div',
    $this->element($element, array('media' => $media, 'index' => $index, 'pos' => $pos)),
    array('class' => "$cell $editable", 'id' => 'media-' . $media['Media']['id'], 'escape' => false));
  $index++;
  if ($index % $columns == 0) {
    echo "<div class=\"clear\"> </div></div>\n";
    echo "<div class=\"row\">";
  }
}
echo '<div class="clear"></div></div>';
?>
</div><!-- cells -->

<div class="p-navigator-pages"><div class="sub">
<a id="select-all"><?php echo __('Select All'); ?></a>
<a id="invert-selection"><?php echo __('Invert Selection'); ?></a>
</div></div>

<?php echo $this->Navigator->pages() ?>

<div id="dialog"></div>
