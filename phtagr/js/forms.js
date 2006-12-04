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


/** Clones all hidden input elements from one form to another recursivly
  @param src Source element
  @param dstForm Element of the destination form */
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
      e.getAttribute("type")=="hidden")
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
function _new_input(type, name, value)
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
function _new_cb(name, value, checked)
{
  var input=_new_input('checkbox', name, value);
  if (checked || checked>0)
    input.setAttribute("checked", "checked");
  return input;
}


/** @param name Name of the select
  @return Returns a new select node */
function _new_select(name)
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
function _new_option(value, text, selected)
{
  var option=document.createElement("option");
  option.setAttribute("value", value);
  if (selected)
    option.setAttribute("selected", "selected");
  option.appendChild(document.createTextNode(text));
  return option;
}

function _new_acl_write(name, value)
{
  var s=_new_select(name);
  s.appendChild(_new_option('keep', 'Keep', true));
  s.appendChild(_new_option('edit', 'Edit', false));
  s.appendChild(_new_option('deny', 'Deny', false));
  return s;
}

function _new_acl_read(name, value)
{
  var s=_new_select(name);
  s.appendChild(_new_option('keep', 'Keep', true));
  s.appendChild(_new_option('preview', 'Preview', false));
  s.appendChild(_new_option('deny', 'Deny', false));
  return s;
}

function _init_form(id)
{
  var form=document.createElement("form");
  form.setAttribute("action", "index.php");
  form.setAttribute("method", "post");

  // copy all hidden inputs from formExplorer or formImage
  // whichever exists
  var srcForm;
  if (document.getElementById("formExplorer"))
    srcForm=document.getElementById("formExplorer");
  else
    srcForm=document.getElementById("formImage");
  _clone_hidden_input(srcForm, form);
 
  form.appendChild(_new_input('hidden', 'image', id));
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
  var t=document.createElement('table');
  var tr=document.createElement('tr');
  var td=document.createElement('td');

  var textarea=document.createElement("textarea");
  textarea.setAttribute("id", focusId);
  textarea.setAttribute("name", "js_caption");
  textarea.setAttribute("cols", 24);
  textarea.setAttribute("rows", 3);
  // encode node content to b64 to catch all special characters
  textarea.appendChild(document.createTextNode(caption));

  td.appendChild(textarea);
  tr.appendChild(td);
  t.appendChild(tr);

  // Buttons
  var tr=document.createElement('tr');
  var td=document.createElement('td');
  _get_buttons(td, nodeId);
  tr.appendChild(td);
  t.appendChild(tr);

  form.appendChild(t);

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

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

  var t=document.createElement('table');
  t.appendChild(_get_row_date(id));
  t.appendChild(_get_row_tags(id));
  t.appendChild(_get_row_sets(id));
  _append_row_locations(id,t);
  t.appendChild(_get_row_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  form.appendChild(t);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

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
  form.appendChild(_new_input('hidden', 'js_acl', 1));

  var t=document.createElement('table');

  t.appendChild(_get_row_groups(images[id]['gid']));
  if (images[id]['gacl']!=null)
    t.appendChild(_get_row_acls(id));
  t.appendChild(_get_row_buttons(nodeId));

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  form.appendChild(t);
  e.appendChild(form);
  document.getElementById(focusId).focus();
}

/**
  @param gid Group id
  @return Row element of ACL */
function _get_row_groups(gid)
{
  var row=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode("Group:"));
  row.appendChild(th);

  var td=document.createElement("td");
  row.appendChild(td);
  var s=document.createElement("select");
  s.setAttribute("size", "1");
  s.setAttribute("name", "js_acl_setgroup");

  var o=document.createElement("option");
  o.setAttribute("value", "-1");
  o.appendChild(document.createTextNode("Keep"));
  s.appendChild(o);

  if (typeof groups !="undefined")
  {
    for(var groupid in groups)
    {
      // skip current group
      if (groupid==gid)
        continue;

      var o=document.createElement("option");
      o.setAttribute("value", groupid);
      o.appendChild(document.createTextNode(groups[groupid]));
      s.appendChild(o);
    }
  }
  td.appendChild(s);
  return row;
}

function _get_row_acls(id)
{
  var gacl=images[id]['gacl'];
  var macl=images[id]['macl'];
  var aacl=images[id]['aacl'];

  var row=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode('ACL:'));
  row.appendChild(th);

  var td=document.createElement('td');
  row.appendChild(td);

  var table=document.createElement("table");
  td.appendChild(table);

  // first row
  var tr=document.createElement("tr");
  
  var td=document.createElement("td");
  tr.appendChild(td);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Write"));
  tr.appendChild(td);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Read"));
  tr.appendChild(td);

  table.appendChild(tr);

  // second row
  tr=tr.cloneNode(false);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Friends"));
  tr.appendChild(td);
  
  td=document.createElement('td');
  td.appendChild(_new_acl_write('js_gacl_write', gacl));
  tr.appendChild(td);
  
  td=document.createElement('td');
  td.appendChild(_new_acl_read('js_gacl_read', gacl));
  tr.appendChild(td);
  table.appendChild(tr);

  // third row
  tr=tr.cloneNode(false);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Members"));
  tr.appendChild(td);
  
  td=document.createElement('td');
  td.appendChild(_new_acl_write('js_macl_write', macl));
  tr.appendChild(td);
  
  td=document.createElement('td');
  td.appendChild(_new_acl_read('js_macl_read', macl));
  tr.appendChild(td);
  table.appendChild(tr);

  // fouth row
  tr=tr.cloneNode(false);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("All"));
  tr.appendChild(td);
  
  td=document.createElement('td');
  td.appendChild(_new_acl_write('js_aacl_write', aacl));
  tr.appendChild(td);
  
  td=document.createElement('td');
  var s=_new_acl_read('js_aacl_read', aacl);
  s.setAttribute('id', 'focus-'+id);
  td.appendChild(s);
  tr.appendChild(td);
  
  table.appendChild(tr);
  return row;
}

/** Row for date
  @param id ID of the image */
function _get_row_date(id)
{
  var tr=document.createElement("tr");

  var th=document.createElement("th");
  th.appendChild(document.createTextNode('Date:'));
  tr.appendChild(th);

  var td=document.createElement("td");
  var input=_new_input('text', 'js_date', images[id]['date']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  
  return tr;
}

/** Row for tags
  @param id ID of the image */
function _get_row_tags(id)
{
  var tr=document.createElement("tr");

  var th=document.createElement("th");
  th.appendChild(document.createTextNode('Tags:'));
  tr.appendChild(th);

  var td=document.createElement("td");
  var te=document.createElement("textarea");
  te.setAttribute('name', 'js_tags');
  te.setAttribute('cols', '24');
  te.setAttribute('rows', '1');
  if (images[id]['tags']!='')
  {
    te.appendChild(document.createTextNode(images[id]['tags']));
  }
  te.setAttribute('id', 'focus-'+id);
  te.setAttribute('class', 'cell');
  td.appendChild(te);
  tr.appendChild(td);
  
  return tr;
}

/** Row for sets
  @param id ID of the image */
function _get_row_sets(id)
{
  var tr=document.createElement("tr");

  var th=document.createElement("th");
  th.appendChild(document.createTextNode('Sets:'));
  tr.appendChild(th);

  var td=document.createElement("td");
  var input=_new_input('text', 'js_sets', images[id]['sets']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  
  return tr;
}

/** Row for location
  @param id ID of the image */
function _append_row_locations(id, t)
{
  var tr=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode('City:'));
  tr.appendChild(th);

  var td=document.createElement("td");
  var input=_new_input('text', 'js_city', images[id]['city']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  t.appendChild(tr);

  var tr=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode('Subloc.:'));
  tr.appendChild(th);

  td=document.createElement('td');
  var input=_new_input('text', 'js_sublocation', images[id]['sublocation']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  t.appendChild(tr);

  var tr=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode('State:'));
  tr.appendChild(th);

  td=document.createElement('td');
  var input=_new_input('text', 'js_state', images[id]['state']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  t.appendChild(tr);

  var tr=document.createElement("tr");
  var th=document.createElement("th");
  th.appendChild(document.createTextNode('Country:'));
  tr.appendChild(th);

  td=document.createElement('td');
  var input=_new_input('text', 'js_country', images[id]['country']);
  input.setAttribute('class', 'cell');
  td.appendChild(input);
  tr.appendChild(td);
  t.appendChild(tr);
}

function _get_buttons(e, nodeId)
{
  if (e==null || nodeId=='')
    return;

  var input=document.createElement("input");
  input.setAttribute("class", "submit");
  input.setAttribute("type", "submit");
  input.setAttribute("value", "Update");
  e.appendChild(input);

  var input=document.createElement("input");
  input.setAttribute("class", "reset");
  input.setAttribute("type", "reset");
  input.setAttribute("value", "Cancel");
  input.setAttribute("onclick", "resetNode('"+nodeId+"')");
  e.appendChild(input);
}

function _get_row_buttons(nodeId)
{
  var tr=document.createElement("tr");
  var td=document.createElement("td");
  tr.appendChild(td);

  var td=document.createElement("td");
  _get_buttons(td, nodeId);
  tr.appendChild(td);

  return tr;
}

/** Removes an input field for uploads
*/
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

function _getLastChildByName(e, name)
{
  return _getChildByName(e, name, -1);
}

function _getFistChildByName(e, name)
{
  return _getChildByName(e, name, 0);
}

/** Adds another input field for uploads
*/
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


