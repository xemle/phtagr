<?php
/*
 * phtagr.
 * 
 * Multi-user image gallery.
 * 
 * Copyright (C) 2006,2007 Sebastian Felis, sebastian@phtagr.org
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2 of the 
 * License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

include_once("$phtagr_lib/SectionBase.php");

class SectionQuickSearch extends SectionBase
{


function SectionQuickSearch()
{
  $this->SectionBase('quicksearch');
}

function print_content()
{
  global $user;
  $url=new Url();
  $url->add_param('section', 'explorer');
  echo "<form action=\"index.php\" method=\"post\">
<p>";
  echo $url->get_form();
  echo "<input type=\"text\" name=\"tags\" class=\"search\" />
<input type=\"submit\" value=\""._("Search")."\" class=\"submit\" /></p>
</form>\n";
  $url->add_param('section', 'search');
  echo "<a href=\"".$url->get_url()."\">"._("Advanced search")."</a>&nbsp;-&nbsp;";
  if ($user->is_anonymous()) {
    $url->add_param('section', 'account');
    $url->add_param('action', 'login');
    $url->add_param('goto', 'home');
    echo "<a href=\"".$url->get_url()."\">"._("Login")."</a>\n";
  } else {
    $url->add_param('section', 'account');
    $url->add_param('action', 'logout');
    echo "<a href=\"".$url->get_url()."\">"._("Logout")."</a>\n";
  }
}

}
?>
