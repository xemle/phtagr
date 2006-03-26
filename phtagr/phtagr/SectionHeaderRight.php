<?php

global $prefix;
include_once("$prefix/SectionBase.php");

class SectionHeaderRight extends SectionBase
{


function SectionHeaderRight()
{
  $this->name="headerright";
}

function print_content()
{
  global $user;
  echo "<form action=\"index.php\" method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"explorer\" />
<input type=\"text\" name=\"tags\" class=\"search\" />
<input type=\"submit\" value=\"search\" class=\"submit\" />
</form>\n";
  echo "<a href=\"index.php?section=search\">advanced search</a>&nbsp;-&nbsp;\n";
  if (!$user->is_auth())
    echo "<a href=\"index.php?section=account&action=login&pass-section=home\">login</a>\n";
  else
    echo "<a href=\"index.php?section=account&action=logout\">logout</a>\n";
}

}
?>
