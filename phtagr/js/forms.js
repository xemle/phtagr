/** Define global data variable to store the contents of replaced elements */
var Data=new Array();

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

