/** Define global data variable to store the contents of replaced elements */
var Data=new Array();

/** Print the node information of a node. The function appends a PRE node to
 * the to node.
  @param from ID of the node, which has to be printed
  @param to ID fo the node, where the debug information hast to be printed */
function _debugNode(from, to)
{
  p=document.getElementById(to);
  e=document.getElementById(from);
  
  var pre=document.createElement("pre");
  var t=document.createTextNode("");
  
  _printNode(t, e, 0, "0");
  pre.appendChild(t);
  p.appendChild(pre);
}

/** Prints recursivly detailed information about the node and add the text data
 * to the text node 
  @param t Textnode
  @param e Current element
  @param depth Current depth
  @param path String of path 
  @return No return value */
function _printNode(t, e, depth, path)
{
  var i, j, cn=0, an=0;
  
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
    _printNode(t, e.childNodes[i], depth+1, path+"."+i);
  }
}

/** Reset a node by the text
  @param id Id of the element
  @param text New text of the node. The text is encoded in B64 */
function reset_text(id)
{
  var e=document.getElementById(id);
  if (e==null)
    return;
  // decode node content from b64
  e.innerHTML=Data[id];
}

function print_caption(id, caption64)
{
  var node="caption-text-"+id;
  var e=document.getElementById(node);
  if (e==null)
    return;

  // Remember old content
  Data[node]=e.innerHTML;
  caption=atob(caption64);
  // encode node content to b64 to catch all special characters
  e.innerHTML=caption+
    " <span class=\"js-button\" onclick=\"reset_text('"+node+"')\">[-]</span>";
}
  
/** Add a form for caption */
function add_form_caption(id, caption64)
{
  var node="caption-"+id;
  var e=document.getElementById(node);
  if (e==null)
    return;

  var i=node+"-edit";
  var text=e.innerHTML;

  // Remember old content
  Data[node]=text;
  caption=atob(caption64);
  // encode node content to b64 to catch all special characters
  e.innerHTML="<form action=\"index.php\" method=\"post\">" +
    "<input type=\"hidden\" name=\"image\" value=\""+id+"\"/>"+
    "<textarea id=\"" + i + "\" name=\"js_caption\" cols=\"24\" rows=\"3\" >" + caption + "</textarea>"+
    "<br/>"+
    "<input class=\"submit\" type=\"submit\" value=\" OK \"/> or "+
    "<input class=\"reset\" type=\"reset\" onclick=\"reset_text('"+node+"')\"/>"+
  "</form>";
  document.getElementById(i).focus();
}

/** Add a form for tags
  @param id ID of the image
  @param tags List of the tags */
function add_form_tags(id, tags)
{
  var node="tag-"+id;
  var e=document.getElementById(node);
  if (e==null)
    return;

  var i=node+"-edit";
  var text=e.innerHTML;

  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[node]!=null && text!=Data[node])
  {
    reset_text(node);
    return;
  }

  // Remember old content
  Data[node]=text;
  // encode node content to b64 to catch all special characters
  e.innerHTML="<form action=\"index.php\" method=\"post\">" +
    "<input type=\"hidden\" name=\"image\" value=\""+id+"\"/>"+
    "<input id=\"" + i + "\" type=\"text\" name=\"js_tags\" value=\"" + tags + "\" size=\"35\"/>"+
    "<br/>"+
    "<input class=\"submit\" type=\"submit\" value=\" OK \"/> or "+
    "<input class=\"reset\" type=\"reset\" onclick=\"reset_text('"+node+"')\"/>"+
  "</form>";
  document.getElementById(i).focus();
}
/** Add a form for acl
  @param id ID of the image
  @param tags List of the tags */
function add_form_acl(id, gacl, oacl, aacl)
{
  var node="acl-"+id;
  var e=document.getElementById(node);
  if (e==null)
    return;

  var i=node+"-acl";
  var text=e.innerHTML;

  // Does a form already exists?
  // On mozilla, the form will be omitted, check also for the next input node
  if (Data[node]!=null && text!=Data[node])
  {
    reset_text(node);
    return;
  }

  // Remember old content
  Data[node]=text;
  // encode node content to b64 to catch all special characters
  s="<form name=\"js_acl\" action=\"index.php\" method=\"post\">" +
    "<input type=\"hidden\" name=\"image\" value=\""+id+"\"/>"+
    "<input type=\"hidden\" name=\"js_acl\" value=\"1\"/>"+
    "<table>"+
    "  <tr>"+
    "    <td></td><td>Friends</td><td>Members</td><td>All</td>"+
    "  </tr>"+
    "  <tr>"+
    "    <td>Edit</td>"+
    "    <td><input type=\"checkbox\" name=\"js_gacl_edit\" value=\"add\"";
  if ((gacl & 0x01)>0) s+=" checked=\"checked\"";
  s+="></td> "+
    "    <td><input type=\"checkbox\" name=\"js_oacl_edit\" value=\"add\"";
  if ((oacl & 0x01)>0) s+=" checked=\"checked\"";
  s+="></td> "+
    "    <td><input type=\"checkbox\" name=\"js_aacl_edit\" value=\"add\"";
  if ((aacl & 0x01)>0) s+=" checked=\"checked\"";
  s+="></td>"+
    "  </tr>"+
    "  <tr>"+
    "    <td>Preview</td>"+
    "    <td><input type=\"checkbox\" name=\"js_gacl_preview\" value=\"add\"";
  if ((gacl & 0xf0)>0) s+=" checked=\"checked\"";
  s+="></td> "+
    "    <td><input type=\"checkbox\" name=\"js_oacl_preview\" value=\"add\"";
  if ((oacl & 0xf0)>0) s+=" checked=\"checked\"";
  s+="></td> "+
    "    <td><input type=\"checkbox\" name=\"js_aacl_preview\" value=\"add\"";
  if ((aacl & 0xf0)>0) s+=" checked=\"checked\"";
  s+="></td>"+
    "  </tr>"+
    "</table>"+
    "<input class=\"submit\" type=\"submit\" value=\" OK \"/> or "+
    "<input class=\"reset\" type=\"reset\" onclick=\"reset_text('"+node+"')\"/>"+
  "</form>";
  e.innerHTML=s;
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
    
  for (var i=0; i<document.forms["formImages"].elements.length; i++) {
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
