(function($) {
  $.fn.selectMedia = function() {
    $(this).each(function() {
      if ($(this).hasClass('selected')) {
        return;
      }
      $(this).addClass('selected');
      $(this).find('.p-explorer-cell-actions ul li .add').hide();
      $(this).find('.p-explorer-cell-actions ul li .del').show();

      var id = $(this).attr('id').split('-')[1];
      var input = $(':input[id=MediaIds]');
      var ids = input.attr('value').split(',');
      
      ids.push(id);
      ids = $.grep(ids, function(n, i) {
        return (n);
      });
      $.unique(ids);
      input.attr('value', ids.join(','));
    });
  };
  $.fn.unselectMedia = function() {
    $(this).each(function() {
      if (!$(this).hasClass('selected')) {
        return;
      }
      $(this).removeClass('selected');
      $(this).find('.p-explorer-cell-actions ul li .add').show();
      $(this).find('.p-explorer-cell-actions ul li .del').hide();

      var id = $(this).attr('id').split('-')[1];
      var input = $(':input[id=MediaIds]');
      var ids = input.attr('value').split(',');
      ids = $.grep(ids, function(n, i) {
        return (n != id);
      });
      input.attr('value', ids.join(','));
    });
  };
  $.fn.invertMediaSelection = function() {
    $(this).each(function() {
      if ($(this).hasClass('selected')) {
        $(this).unselectMedia();
      } else {
        $(this).selectMedia();
      }
    });
  };
})(jQuery);
