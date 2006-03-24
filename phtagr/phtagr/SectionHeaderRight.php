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
    if (!$user->is_auth)
    {
        echo "<a href=\"index.php?section=account&action=login&pass-section=home\">Login</a>\n";
    }
    else
    {
        echo "<a href=\"index.php?section=account&action=logout\">Logout</a>\n";
    }
    echo "<form action=\"index.php\" method=\"post\">
<input type=\"hidden\" name=\"section\" value=\"explorer\" />
<input type=\"text\" name=\"tags\" class=\"search\" />
<input type=\"submit\" value=\"search\" class=\"submit\" />
</form>\n";
}

}
?>
