<?php

global $prefix;
global $db;

include_once("$prefix/SectionBody.php");
include_once("$prefix/Search.php");
include_once("$prefix/Edit.php");
include_once("$prefix/image.php");
include_once("$prefix/sync.php");
include_once("$prefix/Sql.php");


class SectionSearch extends SectionBody
{

function SectionSearch()
{
  $this->name="search";
}

function print_content()
{
  echo "<form action=\"index.php\" method=\"post\">
<div><input type=\"hidden\" name=\"section\" value=\"explorer\" /></div>
<table>
  <tr>
    <th>Tags:</th>
    <td>
      <input type=\"text\" name=\"tags\" size=\"30\" /><br />
      Operation: <input type=\"radio\" name=\"tagop\" value=\"0\" checked /> AND,  
      <input type=\"radio\" name=\"tagop\" value=\"1\" /> OR,  
      <input type=\"radio\" name=\"tagop\" value=\"2\" /> FUZZY
    </td>
  </tr>
  <tr>
    <th>Date:</th>
    <td>
      after: <input type=\"text\" name=\"start\" size=\"10\" />
      before: <input type=\"text\" name=\"end\" size=\"10\" /><br />
      E.g. \"2006-04-21\"
    </td>
  </tr>
  <tr>
    <th></th>
    <td><input type=\"submit\" value=\" Search \" />
        <input type=\"reset\" value=\" Cancel \" /></td>
  </tr>
</table>
</form>
";
}


}

?>
