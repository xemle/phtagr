<?php

include_once("$phtagr_lib/SectionBase.php");

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
<p><input type=\"hidden\" name=\"section\" value=\"explorer\" />
<input type=\"text\" name=\"tags\" class=\"search\" />
<input type=\"submit\" value=\""._("Search")."\" class=\"submit\" /></p>
</form>\n";
  echo "<a href=\"index.php?section=search\">"._("Advanced search")."</a>&nbsp;-&nbsp;";
  if (!$user->is_member())
    echo "<a href=\"index.php?section=account&amp;action=login&amp;goto=home\">"._("Login")."</a>\n";
  else
    echo "<a href=\"index.php?section=account&amp;action=logout\">"._("Logout")."</a>\n";
}

}
?>
