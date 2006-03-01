<?php

global $prefix;
global $db;

include_once("$prefix/SectionBody.php");
include_once("$prefix/Search.php");
include_once("$prefix/image.php");
include_once("$prefix/sync.php");
include_once("$prefix/Sql.php");


class SectionExplorer extends SectionBody
{

function SectionExplorer()
{
    $this->name="explorer";
}

function print_navigator($link, $current, $count)
{
    if ($count<2) return;

    echo "<div class=\"navigator\">\nPage:&nbsp;";
    for ($i=0; $i < $count; $i++)
    {
        if ($i == $current)
        {
            echo "&nbsp;<span class=\"current\">$i</span>&nbsp;\n";
        }
        else if ($i != $current && 
                  ($count <= 10) ||
                  ($i < 3 || $i > $count-4 || 
                  ($i-$current < 4 && $current-$i<4)))
        {
            printf("<a href=\"$link%s\">%d</a>\n", ($i>0?"&page=$i":""), $i);
        }
        else if ($i == $count-4 || $i == 3) 
            echo "&nbsp;...&nbsp;\n";
    }
    echo "</div>\n";
}

function print_edit()
{
  echo "
<fieldset><legend>Edit</legend>
  <table>
    <tr><td class=\"th\">Tags:</td><td><input type=\"text\" name=\"_tags\" size=\"60\"/></td></tr>
    <tr><td class=\"th\">Set:</td><td><input type=\"text\" name=\"_sets\" size=\"60\"/></td></tr>
  </table>
</fieldset>
<input type=\"hidden\" name=\"action\" value=\"edit\"/>
<input type=\"submit\" value=\"OK\" />
<input type=\"reset\" value=\"Reset fields\" />
";
}
function print_content()
{
    global $db;
    global $search;
    global $auth; 

    $sql=$search->get_num_query();
    $result = $db->query($sql);
    if (!$result)
    {
        return;
    }
    $row=mysql_fetch_row($result);
    $count=$row[0];
    
    if (count($search->tags)>0)
    {
      echo "<h2>Explore Tag: ";
      foreach ($search->tags as $tag)
        echo "$tag ";
      echo "</h2>\n";
    }
    else
      echo "<h2>Explore Tags</h2>\n";
  
    if ($count==0)
    {
      echo "<p>No images found!</p>\n";
      return;
    }
    
    $sql=$search->get_query();
    $result = $db->query($sql);
    if (!$result)
    {
      return;
    }
    
    $search_nav=$search;
    $page=$search_nav->page_num;
    $search_nav->page_num=0;
    $url_nav="index.php?section=explorer";
    $url_nav.=$search_nav->to_URL();
    $this->print_navigator($url_nav, $page, ceil($count/$search_nav->page_size));
    
    if ($auth->is_auth())
    {
      echo "<form method=\"post\" action=\"index.php\">";
    }
    echo "<table class=\"tableview\">\n";
    $cell=0;
    while($row = mysql_fetch_row($result)) {
        if ($cell % 2 == 0) {
            echo "<tr>\n";
        }
        echo "<td class=\"preview\">";
        print_preview($row[0]);
        echo "</td>\n\n";
        
        if ($cell % 2 == 1) {
          echo "</tr>\n";
        }
        $cell++;
    }

    echo "</table>";

    $this->print_navigator($url_nav, $page, ceil($count/$search_nav->page_size));
    if ($auth->is_auth())
    {
      echo "<input type=\"hidden\" name=\"section\" value=\"explorer\" />\n";
      echo $search->to_form();  
      $this->print_edit();
      echo "</form>\n";
    }

}

}

?>
