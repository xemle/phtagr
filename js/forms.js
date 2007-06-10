/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
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

/** Define global data variable to store the contents of replaced elements */
var Data=new Array();
var images=new Array();

/** Print the node information of a node. The function appends a PRE node to
 * the to node.
  @param src Source node, which has to be debugged
  @param dst Destination node, where the debug information has to be printed 
  @param maxDepth Maximum of depth*/
function _debugNode(src, dst, maxDepth)
{
  if (src==null || dst==null)
    return;
    
  var t=document.createTextNode("");
  _printNode(t, src, 0, maxDepth, "0");

  var pre=document.createElement("pre");
  pre.appendChild(t);
  dst.appendChild(pre);
}

/** Prints recursivly detailed information about the node and add the text data
 * to the text node 
  @param t Textnode
  @param e Current element
  @param depth Current depth
  @param maxDepth Maximum of depth
  @param path String of path 
  @return No return value */
function _printNode(t, e, depth, maxDepth, path)
{
  var i, j, cn=0, an=0;
  
  if (depth>maxDepth)
    return;

  var text="";
  for (i=0; i<depth; i++)
    text+="  ";
    
  switch (e.nodeType) {
    case 1:
      an=e.attributes.length;
      text+="Element "+e.nodeName;
      break;
    case 2:
      text+="Attribute "+e.nodeName;
      break;
    case 3:
      text+="Text";
      break;
    default:
      text+="Other";
      break;
  }
  if (e.hasChildNodes())
  {
    cn=e.childNodes.length;
    if (cn==1)
      text+=" ("+cn+" child)";
    else
      text+=" ("+cn+" children)";
  }
  
  if (e.nodeValue!=null)
    text+=": '"+e.nodeValue+"'";
  text+=" ["+path+"]";
  text+="\n"; 
  t.nodeValue+=text;

  for (j=0; j<an; j++)
  {
    text="";
    for (i=0; i<depth+1; i++)
      text+="  ";
    text+="@"+e.attributes[j].nodeName+"=";
    text+=e.attributes[j].nodeValue;
    text+="\n";
    t.nodeValue+=text;
  }

  for (i=0; i<cn; i++)
  {
    _printNode(t, e.childNodes[i], depth+1, maxDepth, path+"."+i);
  }
}

/** Resets a node with the old value. The node with ID of nodeId was cloned to
 * the Data array. 
  @param nodeId Node ID of the Data array */
function resetNode(nodeId)
{
  var from=document.getElementById(nodeId);
  var to=Data[nodeId];
  
  if (from==null || to==null)
    return;

  var p=from.parentNode;

  p.replaceChild(to, from);

  Data[nodeId]=null;
}

/** Change the display style of an element
  @param id Id of the element
  @param type Display type */
function setStyleDisplay(id, type)
{
  e=document.getElementById(id);
  if (!e)
    return;
  if (!type || type=="")
    type="inline";
  e.style.display=type;
  return true;
}

function setClassName(id, name)
{
  e=document.getElementById(id);
  if (!e)
    return;
  e.className=name;
}

function collapseFormular(id)
{
  setClassName('fs-'+id, 'formularCollapsed');
  setStyleDisplay('inputs-'+id, 'none'); 
  setStyleDisplay('btn-collapse-'+id, 'none'); 
  setStyleDisplay('btn-expand-'+id, 'inline');
}

function expandFormular(id)
{
  setClassName('fs-'+id, 'formular');
  setStyleDisplay('inputs-'+id, 'block'); 
  setStyleDisplay('btn-collapse-'+id, 'inline'); 
  setStyleDisplay('btn-expand-'+id, 'none');
}


/** Clones all hidden input elements from one form to another recursivly
  @param src Source element
  @param dstForm Element of the destination form 
  @note It copies all hidden input except with the name 'edit' */
function _clone_hidden_input(src, dstForm)
{
  if (src==null || dstForm==null)
  {
    window.allert("null");
    return;
  }
    
  var i,e;
  for (i=0; i<src.childNodes.length; i++)
  {
    e=src.childNodes[i];
    if (e.nodeType==1 &&
      e.nodeName=="INPUT" && 
      e.getAttribute("type")=="hidden" &&
      e.getAttribute("name")!="edit")
      dstForm.appendChild(e.cloneNode(true))
    else
      _clone_hidden_input(e, dstForm);
  }
}

/** Selects all checkboxes
  @param id Id of the refered checkbox
  @param name Name of the checkbox names
*/
function checkbox(id, name)
{
  var cb=document.getElementById(id);
  if (!cb)
    return;
    
  for (var i=0; i<document.forms["formExplorer"].elements.length; i++) {
    var e = document.forms[1].elements[i];
    if (e.name==name && e.type == 'checkbox') {
      e.checked = cb.checked;
    }
  }
}

/** Unchecks all checkboses by an ID
  @param id Ids of the checkboxes */
function uncheck(id)
{
  var cb=document.getElementById(id);
  if (!cb)
    return;
  cb.checked=false;
}

/** Toggle the visibility between two elements. It toggles the style attribute
 * of the node from 'none' with ''. 
  @param fromId First element
  @param toId Second Id */
function toggle_visibility(fromId, toId)
{
  var from=document.getElementById(fromId);
  var to=document.getElementById(toId);

  if (from==null || to==null)
    return;

  if (from.style.display=='none') {
    from.style.display='';
    to.style.display='none';
  } else {
    from.style.display='none';
    to.style.display='';
  }
}

/** Highlight the voting.
  @param id Current voting element
  @param voting Current voting value
  @param i Value of the vote */
function vote_highlight(id, voting, i)
{
  for (j=0; j<=5; j++)
  {
    var s="voting-"+id+"-"+j;
    var e=document.getElementById(s);
    if (!e)
      return;

    var a=e.getAttribute("src");
    if (j<=i) 
      e.setAttribute("src", a.replace(/vote-.*\.png/, "vote-select.png"));
    else if (voting>0 && j<=voting)
      e.setAttribute("src", a.replace(/vote-.*\.png/, "vote-set.png"));
    else
      e.setAttribute("src", a.replace(/vote-.*\.png/, "vote-none.png"));
  }
}

/** Reset the voting stars 
  @param id Id of the current voting
  @param voting Current voting value */
function vote_reset(id, voting)
{
  for (j=0; j<=5; j++) 
  {
    var s="voting-"+id+"-"+j;
    var e=document.getElementById(s);
    if (!e)
      return;

    var a=e.getAttribute("src");
    if (voting>0 && j<=voting)
      e.setAttribute("src", a.replace(/vote-.*\.png/, "vote-set.png"));
    else
      e.setAttribute("src", a.replace(/vote-.*\.png/, "vote-none.png"));
  }
}

/** Returns a new input
  @param type Input type
  @param name Input name
  @param value optional input value
  @return INPUT element */
function _input(type, name, value)
{
  var input=document.createElement("input");
  input.setAttribute("type", type);
  input.setAttribute("name", name);
  if (value!='')
    input.setAttribute("value", value);
  return input;
}

/** Create a new combobox
  @param name
  @param value
  @param checked True of greater zero if the checkbox should be checked */
function _input_checkbox(name, value, checked)
{
  var input=_input('checkbox', name, value);
  if (checked || checked>0)
    input.setAttribute("checked", "checked");
  return input;
}


/** @param name Name of the select
  @return Returns a new select node */
function _select(name)
{
  var select=document.createElement("select");
  select.setAttribute("size", "1");
  select.setAttribute("name", name);
  return select;
}

/** Create an option
  @param value Option value
  @param text Text of the option
  @param selected If true the option will be selected 
  @return The option node */
function _option(value, text, selected)
{
  var option=document.createElement("option");
  option.setAttribute("value", value);
  if (selected)
    option.setAttribute("selected", "selected");
  option.appendChild(document.createTextNode(text));
  return option;
}

function _label(text)
{
  var label=document.createElement('label');
  label.appendChild(document.createTextNode(text));
  return label;
}

/** Returns the acl level of the flag
  @param id Image id
  @param flag Bit flag
  @param mask Bis mask
  @return Returns 3 for any, 2 for member, 1 for group and 0 for private only
  */
function _get_acl_level(id, flag, mask)
{
  var gacl=images[id]['gacl'];
  var macl=images[id]['macl'];
  var pacl=images[id]['pacl'];

  if ((pacl & mask) >= flag) return 3;
  if ((macl & mask) >= flag) return 2;
  if ((gacl & mask) >= flag) return 1;
  return 0;
}

/** Returns a select for acl
  @param name Name of the select
  @param level Acl level
  @return Select node
  @see _get_acl_level */
function _new_acl_select(name, level)
{
  var select=document.createElement('select');
  select.setAttribute('name', name);
  select.setAttribute('size', 1);

  select.appendChild(_option('private', 'Me only', (level==0?true:false)));
  select.appendChild(_option('group', 'Group members', (level==1?true:false)));
  select.appendChild(_option('member', 'All members', (level==2?true:false)));
  select.appendChild(_option('any', 'Everyone', (level==3?true:false)));
  return select;
}

/** @param id ID of the image */
function _init_form(id)
{
  var form=document.createElement("form");
  form.setAttribute("action", "index.php"+"#img-"+id);
  form.setAttribute("method", "post");
  form.setAttribute("accept-charset", "UTF-8");
  form.setAttribute("class", "embeddedform");

  // copy all hidden inputs from formExplorer or formImage
  // whichever exists
  var srcForm;
  if (document.getElementById("formExplorer"))
    srcForm=document.getElementById("formExplorer");
  else
    srcForm=document.getElementById("formImage");
  _clone_hidden_input(srcForm, form);
 
  form.appendChild(_input('hidden', 'image', id));
  return form;
}

/** Prints the whole caption 
  @param id Id of the caption element */
function print_caption(id)
{
  var nodeId="caption-text-"+id;
  var e=document.getElementById(nodeId);
  if (e==null)
    return;

  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }
  
  if (!images[id] || !images[id]['caption'])
    return;

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var caption=images[id]['caption'];

  var text=document.createTextNode(caption+" ");
  
  var span=document.createElement("span");
  span.setAttribute("class", "jsbutton");
  span.setAttribute("onclick", "resetNode('"+nodeId+"')");
  span.appendChild(document.createTextNode("[-]"));
  
  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(text);
  e.appendChild(span);
}
  
/** Insert a form for caption 
  @param id Id of capation element */
function edit_caption(id)
{
  var nodeId="caption-"+id;
  var focusId=nodeId+"-focus";

  var e=document.getElementById(nodeId);
  if (!e)
    return;

  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }

  if (!images[id])
    return;

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var caption=images[id]['caption'];

  var form=_init_form(id);
  form.appendChild(_input('hidden', 'edit', 'js_caption'));
  var fs=document.createElement("fieldset");
  fs.setAttribute('class', 'jsfieldset');
  var legend=document.createElement("legend");
  legend.appendChild(document.createTextNode('Image Caption'));
  fs.appendChild(legend);

  var ol=document.createElement('ol');
  fs.appendChild(ol);
 
  var li=document.createElement('li');
  ol.appendChild(li);

  li.appendChild(_label('Caption:')); 
  var textarea=document.createElement("textarea");
  textarea.setAttribute("id", focusId);
  textarea.setAttribute("name", "js_caption");
  textarea.setAttribute("cols", 24);
  textarea.setAttribute("rows", 3);
  // encode node content to b64 to catch all special characters
  textarea.appendChild(document.createTextNode(caption));

  li.appendChild(textarea);

  form.appendChild(fs);
  form.appendChild(_get_div_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

/** @param id Image ID
  @retrun Input for Meta information */
function edit_tag(id)
{
  var e=document.getElementById('info-'+id);
  if (!e)
    return;

  if (!images[id])
    return;

  var nodeId="info-"+id;
  var focusId="focus-"+id;
  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var form=_init_form(id);
  form.appendChild(_input('hidden', 'edit', 'js_tag'));

  var fs=document.createElement("fieldset");
  fs.setAttribute('class', 'jsfieldset');
  var legend=document.createElement("legend");
  legend.appendChild(document.createTextNode('Image tags'));
  fs.appendChild(legend);

  var ol=document.createElement('ol');
  fs.appendChild(ol);

  ol.appendChild(_get_li_tags(id));

  form.appendChild(fs);
  form.appendChild(_get_div_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

/** @param id Image ID
  @retrun Input for Meta information */
function edit_meta(id)
{
  var e=document.getElementById('info-'+id);
  if (!e)
    return;

  if (!images[id])
    return;

  var nodeId="info-"+id;
  var focusId="focus-"+id;
  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var form=_init_form(id);
  form.appendChild(_input('hidden', 'edit', 'js_meta'));

  var fs=document.createElement("fieldset");
  fs.setAttribute('class', 'jsfieldset');
  var legend=document.createElement("legend");
  legend.appendChild(document.createTextNode('Meta Data'));
  fs.appendChild(legend);

  var ol=document.createElement('ol');
  fs.appendChild(ol);

  ol.appendChild(_get_li_date(id));
  ol.appendChild(_get_li_tags(id));
  ol.appendChild(_get_li_sets(id));
  _add_locations(ol, id);

  form.appendChild(fs);
  form.appendChild(_get_div_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

/** @param id Image ID
  @return Input for ACL */
function edit_acl(id)
{
  var e=document.getElementById('info-'+id);
  if (!e)
    return;

  if (!images[id])
    return;

  var nodeId="info-"+id;
  var focusId="focus-"+id;
  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var form=_init_form(id);
  form.appendChild(_input('hidden', 'js_acl', 1));

  var fs=document.createElement("fieldset");
  fs.setAttribute('class', 'jsfieldset');
  var legend=document.createElement("legend");
  legend.appendChild(document.createTextNode('Access Rights'));
  fs.appendChild(legend);

  var ol=document.createElement('ol');
  fs.appendChild(ol);
  
  ol.appendChild(_get_acl_groups(images[id]['gid']));
  if (images[id]['gacl']!=null) 
  {
    ol.appendChild(_get_acl_edit(id));
    ol.appendChild(_get_acl_preview(id));
  }

  form.appendChild(fs);
  form.appendChild(_get_div_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

/** Creates Group selection box 
  @param gid Group id
  @return Row element of ACL */
function _get_acl_groups(gid)
{
  var li=document.createElement("li");
  li.appendChild(_label("Group:"));

  var s=document.createElement("select");
  li.appendChild(s);
  s.setAttribute("size", "1");
  s.setAttribute("name", "js_acl_setgroup");

  s.appendChild(_option(0, "Keep", false));

  if (typeof groups !="undefined")
  {
    for(var groupid in groups)
    {
      // skip current group
      if (groupid==gid)
        continue;

      s.appendChild(_option(groupid, groups[groupid], false));
    }
  }
  s.appendChild(_option(-1, "Delete group", false));

  return li;
}

/** Returns the input for edit ACL */
function _get_acl_edit(id)
{
  var li=document.createElement('li');
  li.appendChild(_label('Who can edit the meta data?'));
  var level=_get_acl_level(id, 0x02, 0x07);
  li.appendChild(_new_acl_select('js_acl_meta', level));
  return li;
}

/** Returns the input for preview ACL */
function _get_acl_preview(id)
{
  var li=document.createElement('li');
  li.appendChild(_label('Who can preview this image?'));
  var level=_get_acl_level(id, 0x20, 0xe0);
  var select=_new_acl_select('js_acl_preview', level);
  select.setAttribute('id', 'focus-'+id);
  li.appendChild(select);
  return li;
}

/** Row for date
  @param id ID of the image */
function _get_li_date(id)
{
  if (!images || !images[id])
    return null;

  var li=document.createElement("li");
  li.appendChild(_label('Date:'));
  li.appendChild(_input('text', 'js_date', images[id]['date']));
  
  return li;
}

/** Row for tags
  @param id ID of the image */
function _get_li_tags(id)
{
  if (!images || !images[id])
    return null;

  var li=document.createElement("li");

  li.appendChild(_label('Tags:'));

  var te=document.createElement("textarea");
  te.setAttribute('name', 'js_tags');
  te.setAttribute('cols', '10');
  te.setAttribute('rows', '1');
  if (images[id]['tags']!='')
  {
    te.appendChild(document.createTextNode(images[id]['tags']));
  }
  te.setAttribute('id', 'focus-'+id);
  li.appendChild(te);
  
  return li;
}

/** Row for sets
  @param id ID of the image */
function _get_li_sets(id)
{
  if (!images || !images[id])
    return null;

  var li=document.createElement("li");
  li.appendChild(_label('Sets:'));
  li.appendChild(_input('text', 'js_sets', images[id]['sets']));
  return li;
}

/** Row for location
  @param id ID of the image 
  @param t Table object */
function _add_locations(e, id)
{
  if (!e || !id)
    return null;

  if (!images || !images[id])
    return null;

  var li=document.createElement("li");
  li.appendChild(_label('City:'));
  li.appendChild(_input('text', 'js_city', images[id]['city']));
  e.appendChild(li);

  li=document.createElement("li");
  li.appendChild(_label('Sublocation:'));
  li.appendChild(_input('text', 'js_sublocation', images[id]['sublocation']));
  e.appendChild(li);

  li=document.createElement("li");
  li.appendChild(_label('State:'));
  li.appendChild(_input('text', 'js_state', images[id]['state']));
  e.appendChild(li);

  li=document.createElement("li");
  li.appendChild(_label('Country:'));
  li.appendChild(_input('text', 'js_country', images[id]['country']));
  e.appendChild(li);
}

/** Create an Update and an Reset button
  @param nodeId Node ID for the reset button */
function _get_div_buttons(nodeId)
{
  if (nodeId=='')
    return null;

  var div=document.createElement('div');
  div.setAttribute("class", "buttons");

  var submit=_input('submit', '', 'Apply');
  div.appendChild(submit);

  var reset=_input('reset', '', 'Cancel');
  reset.setAttribute("onclick", "resetNode('"+nodeId+"')");
  div.appendChild(reset);

  return div;
}

/** Removes an input field for uploads
  @param id ID of the file input */
function remove_file_input(id)
{
  var e=document.getElementById('upload-'+id);
  if (e==null)
    return;
  var p=e.parentNode;
  p.removeChild(e);
}

/** @param e Current node
  @param name Name of element
  @param i Index. If index is negative, it searches backwards */
function _getChildByName(e, name, i)
{
  if (e==null)
    return null;
  name=name.toUpperCase();
  if (i>=0) 
  {
    var c=-1;
    for (j=0; j<e.childNodes.length; j++)
    {
      if (e.childNodes[j].nodeName==name)
        c++;
      if (c==i)
        return e.chileNodes[j];
      }
  }
  else 
  {
    var c=0;
    for (j=e.childNodes.length-1; j>=0; j--)
    {
      if (e.childNodes[j].nodeName==name)
        c--;
      if (c==i)
        return e.childNodes[j];
      }
  }
}

/** @param e Current node
  @param name Node name
  @return The last child with the given name */
function _getLastChildByName(e, name)
{
  return _getChildByName(e, name, -1);
}

/** @param e current node
  @param name Node name
  @return The first child with the given node name 
function _getFistChildByName(e, name)
{
  return _getChildByName(e, name, 0);
}

/** Adds another input field for uploads
  @param id ID of last file input 
  @param text Text of new file input button */
function add_file_input(id, text)
{
  var row=document.getElementById('upload-'+id);
  if (row==null)
    return;

  var new_row=row.cloneNode(true);

  var td=_getLastChildByName(row, 'td');
  var a=_getLastChildByName(td, 'a');
  if (a!=null)
  {
    a.setAttribute("onclick", "remove_file_input("+id+")");
    a.firstChild.nodeValue=text;
  }

  id++;
  new_row.setAttribute("id", "upload-"+id);
  td=_getLastChildByName(new_row, 'td');
  a=_getLastChildByName(td, 'a');
  if (a!=null)
  {
    a.setAttribute("onclick", "add_file_input("+id+", '"+text+"')");
  }

  p=row.parentNode;
  p.appendChild(new_row);
  return;
}


