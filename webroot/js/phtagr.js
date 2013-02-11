/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr v 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */


/** Defaults function from http://parentNode.org */
Function.prototype.defaults = function()
{
  var _f = this;
  var _a = Array(_f.length-arguments.length).concat(
    Array.prototype.slice.apply(arguments));
  return function()
  {
    return _f.apply(_f, Array.prototype.slice.apply(arguments).concat(
      _a.slice(arguments.length, _a.length)));
  }
}

var toggleVisibility = function(id, type) {
  var e = null;
  e = document.getElementById(id);

  if (e) {
    if (e.style.display == type)
      e.style.display = 'none'; 
    else
      e.style.display = type;
  }
}.defaults(-1, 'block');

var selectMedia = function(id) {
  var e = document.getElementById('select-'+id);
  if (!e)
    return false;
  var thumb = document.getElementById('media-'+id);

  var add = false;
  if (e.checked) {
    thumb.className=thumb.className.replace(' unselected', ' selected');
    add = true;
  } else {
    thumb.className=thumb.className.replace(' selected', ' unselected');
  }

  // adapt the id list for the hidden input
  var list = document.getElementById('MediaIds');
  var ids;
  if (list.value.length > 0)
    ids = list.value.split(',');
  else
    ids = new Array();

  for(var i=ids.length-1; i>=0; i--) {
    // id found! delete or mark existing
    if (ids[i] == id) {
      if (!add)
        ids.splice(i,1);
      else 
        id = -1;
    }
  }

  if (add && id > 0)
    ids.push(id);

  list.value = ids.join(',');
};

var thumbSelectAll = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = true;
    selectMedia(mediaIds[i]);
  }
};

var thumbSelectInvert = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = !e.checked;
    selectMedia(mediaIds[i]);
  }
};

/** Selects the table row from the state of the current checkboxs somewhere
 * below the table row element
  @param checkBox Element of the checkbox */
var selectTableRow = function(checkBox) {
  if (checkBox == null) {
    return false;
  }

  var p = checkBox.parentNode;
  while (p && p.nodeName.toUpperCase() != 'TR') {
    p = p.parentNode;
  }
  if (!p) {
    return false;
  }

  if (checkBox.checked) {
    p.className += ' selected';
  } else {
    p.className = p.className.replace(' selected', '');
  }
};

var activateTab = function(id, name) {
  var header = document.getElementById(name+'-header-'+id);
  var content = document.getElementById(name+'-content-'+id);
  if (!header || !content) {
    return false;
  }
  if (header.className.indexOf('tabActive') < 0) {
    header.className += ' tabActive';
  }
  if (content.className.indexOf('tabHidden') >= 0) {
    content.className = content.className.replace(/\s*tabHidden/g, '');
  }

  // disable others
  var i = 0;
  header = document.getElementById(name+'-header-'+i);
  content = document.getElementById(name+'-content-'+i);
  while (header && content) {
    if (i != id && header.className.indexOf('tabActive') >= 0) {
      header.className = header.className.replace(/\s*tabActive/, '');
    }
    if (i != id && content.className.indexOf('tabHidden') < 0) {
      content.className += ' tabHidden';
    }
    i++;
    header = document.getElementById(name+'-header-'+i);
    content = document.getElementById(name+'-content-'+i);
  }
}.defaults(-1, 'tab');
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr v 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

/** Defaults function from http://parentNode.org */
Function.prototype.defaults = function()
{
  var _f = this;
  var _a = Array(_f.length-arguments.length).concat(
    Array.prototype.slice.apply(arguments));
  return function()
  {
    return _f.apply(_f, Array.prototype.slice.apply(arguments).concat(
      _a.slice(arguments.length, _a.length)));
  }
}

var toggleVisibility = function(id, type) {
  var e = null;
  e = document.getElementById(id);

  if (e) {
    if (e.style.display == type)
      e.style.display = 'none'; 
    else
      e.style.display = type;
  }
}.defaults(-1, 'block');

var selectMedia = function(id) {
  var e = document.getElementById('select-'+id);
  if (!e)
    return false;
  var thumb = document.getElementById('media-'+id);

  var add = false;
  if (e.checked) {
    thumb.className=thumb.className.replace(' unselected', ' selected');
    add = true;
  } else {
    thumb.className=thumb.className.replace(' selected', ' unselected');
  }

  // adapt the id list for the hidden input
  var list = document.getElementById('MediaIds');
  var ids;
  if (list.value.length > 0)
    ids = list.value.split(',');
  else
    ids = new Array();

  for(var i=ids.length-1; i>=0; i--) {
    // id found! delete or mark existing
    if (ids[i] == id) {
      if (!add)
        ids.splice(i,1);
      else 
        id = -1;
    }
  }

  if (add && id > 0)
    ids.push(id);

  list.value = ids.join(',');
};

var thumbSelectAll = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = true;
    selectMedia(mediaIds[i]);
  }
};

var thumbSelectInvert = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = !e.checked;
    selectMedia(mediaIds[i]);
  }
};

/** Selects the table row from the state of the current checkboxs somewhere
 * below the table row element
  @param checkBox Element of the checkbox */
var selectTableRow = function(checkBox) {
  if (checkBox == null) {
    return false;
  }

  var p = checkBox.parentNode;
  while (p && p.nodeName.toUpperCase() != 'TR') {
    p = p.parentNode;
  }
  if (!p) {
    return false;
  }

  if (checkBox.checked) {
    p.className += ' selected';
  } else {
    p.className = p.className.replace(' selected', '');
  }
};

var activateTab = function(id, name) {
  var header = document.getElementById(name+'-header-'+id);
  var content = document.getElementById(name+'-content-'+id);
  if (!header || !content) {
    return false;
  }
  if (header.className.indexOf('tabActive') < 0) {
    header.className += ' tabActive';
  }
  if (content.className.indexOf('tabHidden') >= 0) {
    content.className = content.className.replace(/\s*tabHidden/g, '');
  }

  // disable others
  var i = 0;
  header = document.getElementById(name+'-header-'+i);
  content = document.getElementById(name+'-content-'+i);
  while (header && content) {
    if (i != id && header.className.indexOf('tabActive') >= 0) {
      header.className = header.className.replace(/\s*tabActive/, '');
    }
    if (i != id && content.className.indexOf('tabHidden') < 0) {
      content.className += ' tabHidden';
    }
    i++;
    header = document.getElementById(name+'-header-'+i);
    content = document.getElementById(name+'-content-'+i);
  }
}.defaults(-1, 'tab');
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

/** Defaults function from http://parentNode.org */
Function.prototype.defaults = function()
{
  var _f = this;
  var _a = Array(_f.length-arguments.length).concat(
    Array.prototype.slice.apply(arguments));
  return function()
  {
    return _f.apply(_f, Array.prototype.slice.apply(arguments).concat(
      _a.slice(arguments.length, _a.length)));
  }
}

var toggleVisibility = function(id, type) {
  var e = null;
  e = document.getElementById(id);

  if (e) {
    if (e.style.display == type)
      e.style.display = 'none'; 
    else
      e.style.display = type;
  }
}.defaults(-1, 'block');

var selectMedia = function(id) {
  var e = document.getElementById('select-'+id);
  if (!e)
    return false;
  var thumb = document.getElementById('media-'+id);

  var add = false;
  if (e.checked) {
    thumb.className=thumb.className.replace(' unselected', ' selected');
    add = true;
  } else {
    thumb.className=thumb.className.replace(' selected', ' unselected');
  }

  // adapt the id list for the hidden input
  var list = document.getElementById('MediaIds');
  var ids;
  if (list.value.length > 0)
    ids = list.value.split(',');
  else
    ids = new Array();

  for(var i=ids.length-1; i>=0; i--) {
    // id found! delete or mark existing
    if (ids[i] == id) {
      if (!add)
        ids.splice(i,1);
      else 
        id = -1;
    }
  }

  if (add && id > 0)
    ids.push(id);

  list.value = ids.join(',');
};

var thumbSelectAll = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = true;
    selectMedia(mediaIds[i]);
  }
};

var thumbSelectInvert = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = !e.checked;
    selectMedia(mediaIds[i]);
  }
};

/** Selects the table row from the state of the current checkboxs somewhere
 * below the table row element
  @param checkBox Element of the checkbox */
var selectTableRow = function(checkBox) {
  if (checkBox == null) {
    return false;
  }

  var p = checkBox.parentNode;
  while (p && p.nodeName.toUpperCase() != 'TR') {
    p = p.parentNode;
  }
  if (!p) {
    return false;
  }

  if (checkBox.checked) {
    p.className += ' selected';
  } else {
    p.className = p.className.replace(' selected', '');
  }
};

var activateTab = function(id, name) {
  var header = document.getElementById(name+'-header-'+id);
  var content = document.getElementById(name+'-content-'+id);
  if (!header || !content) {
    return false;
  }
  if (header.className.indexOf('tabActive') < 0) {
    header.className += ' tabActive';
  }
  if (content.className.indexOf('tabHidden') >= 0) {
    content.className = content.className.replace(/\s*tabHidden/g, '');
  }

  // disable others
  var i = 0;
  header = document.getElementById(name+'-header-'+i);
  content = document.getElementById(name+'-content-'+i);
  while (header && content) {
    if (i != id && header.className.indexOf('tabActive') >= 0) {
      header.className = header.className.replace(/\s*tabActive/, '');
    }
    if (i != id && content.className.indexOf('tabHidden') < 0) {
      content.className += ' tabHidden';
    }
    i++;
    header = document.getElementById(name+'-header-'+i);
    content = document.getElementById(name+'-content-'+i);
  }
}.defaults(-1, 'tab');
/**
 * PHP versions 5
 *
 * phTagr : Tag, Browse, and Share Your Photos.
 * Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 *
 * Licensed under The GPL-2.0 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2013, Sebastian Felis (sebastian@phtagr.org)
 * @link          http://www.phtagr.org phTagr
 * @package       Phtagr
 * @since         phTagr 2.2b3
 * @license       GPL-2.0 (http://www.opensource.org/licenses/GPL-2.0)
 */

/** Defaults function from http://parentNode.org */
Function.prototype.defaults = function()
{
  var _f = this;
  var _a = Array(_f.length-arguments.length).concat(
    Array.prototype.slice.apply(arguments));
  return function()
  {
    return _f.apply(_f, Array.prototype.slice.apply(arguments).concat(
      _a.slice(arguments.length, _a.length)));
  }
}

var toggleVisibility = function(id, type) {
  var e = null;
  e = document.getElementById(id);

  if (e) {
    if (e.style.display == type)
      e.style.display = 'none'; 
    else
      e.style.display = type;
  }
}.defaults(-1, 'block');

var selectMedia = function(id) {
  var e = document.getElementById('select-'+id);
  if (!e)
    return false;
  var thumb = document.getElementById('media-'+id);

  var add = false;
  if (e.checked) {
    thumb.className=thumb.className.replace(' unselected', ' selected');
    add = true;
  } else {
    thumb.className=thumb.className.replace(' selected', ' unselected');
  }

  // adapt the id list for the hidden input
  var list = document.getElementById('MediaIds');
  var ids;
  if (list.value.length > 0)
    ids = list.value.split(',');
  else
    ids = new Array();

  for(var i=ids.length-1; i>=0; i--) {
    // id found! delete or mark existing
    if (ids[i] == id) {
      if (!add)
        ids.splice(i,1);
      else 
        id = -1;
    }
  }

  if (add && id > 0)
    ids.push(id);

  list.value = ids.join(',');
};

var thumbSelectAll = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = true;
    selectMedia(mediaIds[i]);
  }
};

var thumbSelectInvert = function() {
  var e = null;
  for(var i in mediaIds) {
    e = document.getElementById('select-'+mediaIds[i]);
    if (!e)
      continue;
    e.checked = !e.checked;
    selectMedia(mediaIds[i]);
  }
};

/** Selects the table row from the state of the current checkboxs somewhere
 * below the table row element
  @param checkBox Element of the checkbox */
var selectTableRow = function(checkBox) {
  if (checkBox == null) {
    return false;
  }

  var p = checkBox.parentNode;
  while (p && p.nodeName.toUpperCase() != 'TR') {
    p = p.parentNode;
  }
  if (!p) {
    return false;
  }

  if (checkBox.checked) {
    p.className += ' selected';
  } else {
    p.className = p.className.replace(' selected', '');
  }
};

var activateTab = function(id, name) {
  var header = document.getElementById(name+'-header-'+id);
  var content = document.getElementById(name+'-content-'+id);
  if (!header || !content) {
    return false;
  }
  if (header.className.indexOf('tabActive') < 0) {
    header.className += ' tabActive';
  }
  if (content.className.indexOf('tabHidden') >= 0) {
    content.className = content.className.replace(/\s*tabHidden/g, '');
  }

  // disable others
  var i = 0;
  header = document.getElementById(name+'-header-'+i);
  content = document.getElementById(name+'-content-'+i);
  while (header && content) {
    if (i != id && header.className.indexOf('tabActive') >= 0) {
      header.className = header.className.replace(/\s*tabActive/, '');
    }
    if (i != id && content.className.indexOf('tabHidden') < 0) {
      content.className += ' tabHidden';
    }
    i++;
    header = document.getElementById(name+'-header-'+i);
    content = document.getElementById(name+'-content-'+i);
  }
}.defaults(-1, 'tab');
