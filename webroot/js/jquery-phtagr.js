(function($) {
  $.fn.toggleMedia = function() {
    if ($(this).hasClass('selected')) {
      $(this).unselectMedia();
    } else {
      $(this).selectMedia();
    }
  };
  $.fn.selectMedia = function() {
    $(this).each(function() {
      if ($(this).hasClass('selected')) {
        return;
      }
      $(this).addClass('selected');
      $(this).find('.actions li.add').hide();
      $(this).find('.actions li.del').show();

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
      $(this).find('.actions li.add').show();
      $(this).find('.actions li.del').hide();

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

  $.fn.cakeAutoComplete = function(fieldId, url, options) {
    var options = options || {};
    var names = fieldId.split('.');
    var data = { data: {} };
    var val = data['data'];
    for(var i = 0; i < names.length - 1; i++) {
      val[names[i]] = {};
      val = val[names[i]];
    }
    $(this).bind( "keydown", function( event ) {
      if ( event.keyCode === $.ui.keyCode.TAB &&
          $( this ).data( "autocomplete" ).menu.active ) {
        event.preventDefault();
      }
    })
    .autocomplete({
      minLength: 0,
      source: function( request, response ) {
        // delegate back to autocomplete, but extract the last term
        var input = request.term;
        if (options['split']) {
          input = input.split(/,\s*/).pop();
        }
        val[names[names.length - 1]] = input;
        $.post(url, data, function(data) {
          var result = new Array();
          $(data).find('li').each(function() {
            result.push($(this).text());
          });
          response(result);
        }, 'xml');
      },
      focus: function() {
        // prevent value inserted on focus
        return false;
      },
      select: function( event, ui ) {
        if (options['split']) {
          var terms = this.value.split(/,\s*/);
          terms.pop();
          terms.push( ui.item.value );
          terms.push( "" );
          this.value = terms.join( ", " );
        } else {
          this.value = ui.item.value;
        }
        return false;
      }
    }).keydown(function(e){
      if (e.keyCode === 13 && options['submitOnEnter']) {
        if (!this.value.match(/:$/)) {
          $(this).closest('form').submit();
        } else {
          $(this).autocomplete('search', this.value);
        }
      }
    });
  };
  $.fn.delayedHover = function(over, out, delay) {
    var timerId = false;
    $(this).hover(function() {
      timerId = setTimeout(function() {
        over();
      }, delay);
    }, function() {
      if (timerId) {
        clearTimeout(timerId);
      }
      out();
    });
  };
})(jQuery);
