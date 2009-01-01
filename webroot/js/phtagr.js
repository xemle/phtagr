/*
 * phtagr.
 *
 * Multi-user image gallery.
 *
 * Copyright (C) 2006-2009 Sebastian Felis, sebastian@phtagr.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the
 * License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
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

var selectImage = function(id) {
  var e = document.getElementById('select-'+id);
  if (!e)
    return false;
  var thumb = document.getElementById('image-'+id);

  var add = false;
  if (e.checked) {
    thumb.className=thumb.className.replace(' unselected', ' selected');
    add = true;
  } else {
    thumb.className=thumb.className.replace(' selected', ' unselected');
  }

  // adapt the id list for the hidden input
  var list = document.getElementById('ImageIds');
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
  for(var id in imageData) {
    e = document.getElementById('select-'+id);
    if (!e)
      continue;
    e.checked = true;
    selectImage(id);
  }
};

var thumbSelectInvert = function() {
  var e = null;
  for(var id in imageData) {
    e = document.getElementById('select-'+id);
    if (!e)
      continue;
    e.checked = !e.checked;
    selectImage(id);
  }
};

