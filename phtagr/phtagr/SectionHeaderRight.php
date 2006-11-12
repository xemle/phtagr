<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionHeaderRight extends SectionBase
{


function SectionHeaderRight()
{
  $this->SectionBase('headerright');
}

function print_content()
{
  global $user;
  $url=new Url();
  $url->add_param('section', 'explorer');
  echo "<form action=\"index.php\" method=\"post\">
<p>";
  echo $url->to_form();
  echo "<input type=\"text\" name=\"tags\" class=\"search\" />
<input type=\"submit\" value=\""._("Search")."\" class=\"submit\" /></p>
</form>\n";
  $url->add_param('section', 'search');
  echo "<a href=\"".$url->to_URL()."\">"._("Advanced search")."</a>&nbsp;-&nbsp;";
  if ($user->is_anonymous()) {
    $url->add_param('section', 'account');
    $url->add_param('action', 'login');
    $url->add_param('goto', 'home');
    echo "<a href=\"".$url->to_URL()."\">"._("Login")."</a>\n";
  } else {
    $url->add_param('section', 'account');
    $url->add_param('action', 'logout');
    echo "<a href=\"".$url->to_URL()."\">"._("Logout")."</a>\n";
  }
}

}
?>
