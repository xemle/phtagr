/** Define global data variable to store the contents of replaced elements */
var Data=new Array();

/** Print the node information of a node. The function appends a PRE node to
 * the to node.
  @param src Source node, which has to be debugged
  @param dst Destination node, where the debug information hast to be printed 
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

function print_caption(id, caption64)
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
  
  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  caption=atob(caption64);
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
  
/** Add a form for caption */
function add_form_caption(id, caption64)
{
  var nodeId="caption-"+id;
  var e=document.getElementById(nodeId);
  if (e==null)
    return;

  var focusId=nodeId+"-edit";

  // Remember old content
  Data[nodeId]=e.cloneNode(true);
  
  var form=document.createElement("form");
  form.setAttribute("action", "index.php");
  form.setAttribute("method", "post");

  // copy all hidden inputs from formExplorer
  var srcForm=document.getElementById("formExplorer");
  _clone_hidden_input(srcForm, form);
  
  var input=document.createElement("input");
  input.setAttribute("type", "hidden");
  input.setAttribute("name", "image");
  input.setAttribute("value", id);
  form.appendChild(input);

  var textarea=document.createElement("textarea");
  textarea.setAttribute("id", focusId);
  textarea.setAttribute("name", "js_caption");
  textarea.setAttribute("cols", 24);
  textarea.setAttribute("rows", 3);
  form.appendChild(textarea);

  // encode node content to b64 to catch all special characters
  var text=document.createTextNode(atob(caption64));
  textarea.appendChild(text);

  var br=document.createElement("br");
  form.appendChild(br);
  
  input=document.createElement("input");
  input.setAttribute("class", "submit");
  input.setAttribute("type", "submit");
  input.setAttribute("value", " OK ");
  form.appendChild(input);

  var text=document.createTextNode(" or ");
  form.appendChild(text);
  
  input=document.createElement("input");
  input.setAttribute("class", "reset");
  input.setAttribute("type", "reset");
  input.setAttribute("onclick", "resetNode('"+nodeId+"')");
  form.appendChild(input);

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);

  document.getElementById(focusId).focus();
}

function _clone_hidden_input(srcForm, dstForm)
{
  if (srcForm==null || dstForm==null)
    return;
    
  var i,input;
  for (i=0; i<srcForm.childNodes.length; i++)
  {
    input=srcForm.childNodes[i];
    if (input.nodeType==1 &&
      input.nodeName=="INPUT" && 
      input.getAttribute("type")=="hidden")
      dstForm.appendChild(input.cloneNode(true));
  }
}

/** Add a form for tags
  @param id ID of the image
  @param tags List of the tags */
function add_form_tags(id, tags)
{
  var nodeId="tag-"+id;
  var e=document.getElementById(nodeId);
  if (e==null)
    return;

  var focusId=nodeId+"-edit";

  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[nodeId]!=null)
  {
    resetNode(nodeId);
    return;
  }

  // Remember old content
  Data[nodeId]=e.cloneNode(true);

  var form=document.createElement("form");
  form.setAttribute("action", "index.php");
  form.setAttribute("method", "post");

  // copy all hidden inputs from formExplorer
  var srcForm=document.getElementById("formExplorer");
  _clone_hidden_input(srcForm, form);
  
  var input=document.createElement("input");
  input.setAttribute("type", "hidden");
  input.setAttribute("name", "image");
  input.setAttribute("value", id);
  form.appendChild(input);

  input=document.createElement("input");
  input.setAttribute("id", focusId);
  input.setAttribute("type", "text");
  input.setAttribute("name", "js_tags");
  input.setAttribute("value", tags);
  input.setAttribute("size", 35);
  form.appendChild(input);
  
  input=document.createElement("input");
  input.setAttribute("class", "submit");
  input.setAttribute("type", "submit");
  input.setAttribute("value", " OK ");
  form.appendChild(input);

  var text=document.createTextNode(" or ");
  form.appendChild(text);
  
  input=document.createElement("input");
  input.setAttribute("class", "reset");
  input.setAttribute("type", "reset");
  input.setAttribute("onclick", "resetNode('"+nodeId+"')");
  form.appendChild(input);

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  
  document.getElementById(focusId).focus();
}
/** Add a form for acl
  @param id ID of the image
  @param tags List of the tags */
function add_form_acl(id, gacl, oacl, aacl)
{
  var nodeId="acl-"+id;
  var e=document.getElementById(nodeId);
  if (e==null)
    return;

  var focusId=nodeId+"-acl";

  // Does a form already exists?
  if (Data[nodeId]!=null)
  {
    resetNod(nodeId);
    return;
  }

  // Remember old content
  Data[nodeId]=e.cloneNode(true);
  
  var form=document.createElement("form");
  form.setAttribute("action", "index.php");
  form.setAttribute("method", "post");

  // copy all hidden inputs from formExplorer
  var srcForm=document.getElementById("formExplorer");
  _clone_hidden_input(srcForm, form);
  
  var input=document.createElement("input");
  input.setAttribute("type", "hidden");
  input.setAttribute("name", "image");
  input.setAttribute("value", id);
  form.appendChild(input);

  input=input.cloneNode(false);
  input.setAttribute("name", "js_acl");
  input.setAttribute("value", "yes");
  form.appendChild(input);
  
  var table=document.createElement("table");
  // first row
  var tr=document.createElement("tr");
  
  var td=document.createElement("td");
  tr.appendChild(td);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Friends"));
  tr.appendChild(td);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Members"));
  tr.appendChild(td);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("All"));
  tr.appendChild(td);

  table.appendChild(tr);

  // second row
  tr=tr.cloneNode(false);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Edit"));
  tr.appendChild(td);
  
  td=td.cloneNode(false);
  var input=document.createElement("input");
  input.setAttribute("id", focusId);
  input.setAttribute("type", "checkbox");
  input.setAttribute("name", "js_gacl_edit");
  input.setAttribute("value", "add");
  if ((gacl & 0x01)>0) 
    input.setAttribute("checked", "checked");
  td.appendChild(input);
  input.appendChild(document.createTextNode(gacl+"Super"));
  tr.appendChild(td);
  
  td=td.cloneNode(true);
  input=td.childNodes[0];
  input.removeAttribute("id");
  input.setAttribute("name", "js_oacl_edit");
  if ((oacl & 0x01)>0)
    input.setAttribute("checked", "checked");
  else
    input.removeAttribute("checked");
  tr.appendChild(td);
  
  td=td.cloneNode(true);
  input=td.childNodes[0];
  input.setAttribute("name", "js_aacl_edit");
  if ((aacl & 0x01)>0)
    input.setAttribute("checked", "checked");
  else
    input.removeAttribute("checked");
  tr.appendChild(td);
  
  table.appendChild(tr);
  
  // third row
  tr=tr.cloneNode(false);

  td=td.cloneNode(false);
  td.appendChild(document.createTextNode("Preview"));
  tr.appendChild(td);
  
  td=td.cloneNode(false);
  var input=document.createElement("input");
  input.setAttribute("type", "checkbox");
  input.setAttribute("name", "js_gacl_preview");
  input.setAttribute("value", "add");
  if ((gacl & 0xf0)>0) 
    input.setAttribute("checked", "checked");
  td.appendChild(input);
  tr.appendChild(td);
  
  td=td.cloneNode(true);
  input=td.childNodes[0];
  input.setAttribute("name", "js_oacl_preview");
  if ((oacl & 0xf0)>0)
    input.setAttribute("checked", "checked");
  else
    input.removeAttribute("checked");
  tr.appendChild(td);
  
  td=td.cloneNode(true);
  input=td.childNodes[0];
  input.setAttribute("name", "js_aacl_preview");
  if ((aacl & 0xf0)>0)
    input.setAttribute("checked", "checked");
  else
    input.removeAttribute("checked");
  tr.appendChild(td);
  
  table.appendChild(tr);
  form.appendChild(table); 

  input=document.createElement("input");
  input.setAttribute("class", "submit");
  input.setAttribute("type", "submit");
  input.setAttribute("value", " OK ");
  form.appendChild(input);

  var text=document.createTextNode(" or ");
  form.appendChild(text);
  
  input=document.createElement("input");
  input.setAttribute("class", "reset");
  input.setAttribute("type", "reset");
  input.setAttribute("onclick", "resetNode('"+nodeId+"')");
  form.appendChild(input);

  while (e.hasChildNodes())
    e.removeChild(e.lastChild);
  e.appendChild(form);
  
  document.getElementById(focusId).focus();
}

/** Checks all checkboxes
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

function uncheck(id)
{
  var cb=document.getElementById(id);
  if (!cb)
    return;
  cb.checked=false;
}
