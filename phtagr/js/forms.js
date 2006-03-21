/** Define global variable */
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

/** Add a form for tags
  @param id ID of the image
  @param tags List of the tags */
function add_form_tags(id, tags)
{
  var node=id+"-tag";
  var e=document.getElementById(node);
  if (e==null)
    return;

  var i=id+"-edit";
  var text=e.innerHTML;

  //text_enc=btoa(text); 
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
    "<input class=\"submit\" type=\"submit\" value=\"Send\"/> or "+
    "<input class=\"reset\" type=\"reset\" onclick=\"reset_text('"+node+"')\"/>"+
  "</form>";
  document.getElementById(i).focus();
}

