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
include_once("$phtagr_lib/Search.php");
include_once("$phtagr_lib/Edit.php");
include_once("$phtagr_lib/Database.php");

class SectionSearch extends SectionBase
{

function SectionSearch()
{
  $this->name="search";
}

function print_content()
{
  global $user;
  echo "<h2>"._("Advanced Search")."</h2>\n";
  $url=new Url();
  $url->add_param('section', 'explorer');

  echo "<form action=\"index.php\" method=\"post\">\n";
  echo "<fieldset><legend>"._("General")."</legend>\n";
  echo $url->get_form();

  echo "<ol>";

  echo "<li>";
  $this->label(_("Tags:"));
  $this->input_text("tags", "", 30);
  echo "</li>";

  echo "<li>";
  $this->label(_("Tag Operation:"));
  echo "<select name=\"tagop\" size=\"1\">\n";
  $this->option(_("AND"), 0);
  $this->option(_("OR"), 1);
  $this->option(_("FUZZY"), 2);
  echo "</select>";
  echo "</li>";

  echo "<li>";
  $this->label(_("Sets:"));
  $this->input_text("sets", "", 30);
  echo "</li>";
  echo "<li>";
  $this->label(_("Set Operation:"));
  echo "<select name=\"setop\" size=\"1\">\n";
  $this->option(_("AND"), 0);
  $this->option(_("OR"), 1);
  $this->option(_("FUZZY"), 2);
  echo "</select>";
  echo "</li>";

  echo "<li>";
  $this->label(_("Location:"));
  echo "<p>";
  $this->input_text("locations", "", 30);
  echo _("Type:")."<select name=\"location_type\" size=\"1\">\n";
  $this->option(_("Any"), LOCATION_UNDEFINED);
  $this->option(_("City"), LOCATION_CITY);
  $this->option(_("Subloction"), LOCATION_SUBLOCATION);
  $this->option(_("State"), LOCATION_STATE);
  $this->option(_("Country"), LOCATION_COUNTRY);
  echo "</select>\n";
  echo "</p>";
  echo "</li>";

  echo "<li>";
  $this->label(_("From:"));
  echo "<p>";
  $this->input_text("start", "", 10, 10); 
  echo _("(Format: YYYY-MM-DD)")."</p>";
  echo "</li>";

  echo "<li>";
  $this->label(_("Until:"));
  echo "<p>";
  $this->input_text("end", "", 10, 10); 
  echo _("(Format: YYYY-MM-DD)")."</p>";
  echo "</li>";
  
  echo "<li>";
  $this->label(_("File type:"));
  echo "<select size=\"1\" name=\"filetype\">\n";
  $this->option(_("Any"), "any", true);
  $this->option(_("Image"), "image");
  $this->option(_("Video"), "video");
  echo "</select>\n";
  echo "</li>";

  echo "</ol>";
  echo "</fieldset>\n";

  echo "<fieldset><legend>"._("Advanced")."</legend>\n";
  echo "<ol>";

  echo "<li>";
  $this->label(_("User:"));
  $this->input_text("user");
  echo "</li>";

  if ($user->is_member())
  {
    $groups=$user->get_groups();
    if (count($groups)>0)
    {
      echo "<li>";
      $this->label(_("Groups:"));
      echo "<select size=\"1\" name=\"group\">\n";
      $this->option(_("None"), -1, true);

      foreach ($groups as $gid => $name)
        $this->option($name, $gid);
      $this->option(_("Not assigned"), 0);
      echo "</select>\n";
      echo "</li>";
    }
  
    echo "<li>";
    $this->label(_("Visibility:"));
    echo "<select size=\"1\" name=\"visibility\">\n";
    $this->option(_("None"), "none", true);
    $this->option(_("Private"), "private");
    $this->option(_("Group"), "group");
    $this->option(_("Member"), "member");
    $this->option(_("Public"), "public");
    echo "</select>\n";
    echo "</li>";
  }

  echo "<li>";
  $this->label(_("Sort by:"));
  echo "<select name=\"orderby\">\n";
  $this->option(_("date"), "date", true);
  $this->option(_("date ascending"), "-date");
  $this->option(_("Popularity"), "popularity");
  $this->option(_("Popularity ascending"), "-popularity");
  $this->option(_("Voting"), "voting");
  $this->option(_("Voting ascending"), "-voting");
  $this->option(_("Newest"), "newest");
  $this->option(_("Newest ascending"), "-newest");
  $this->option(_("Changes"), "changes");
  $this->option(_("Changes ascending"), "-changes");
  $this->option(_("Random"), "random");
  echo "</select>\n";
  echo "</li>";

  echo "<li>";
  $this->label(_("Page size:"));
  echo "<select name=\"pagesize\">\n";
  foreach (array(12, 24, 60, 120, 240) as $size)
    $this->option($size, $size);
  echo "</select>\n";
  echo "</li>";

  echo "</ol>";
  echo "</fieldset>";
  
  echo "<p>";
  $this->input_submit(_("Search"));
  $this->input_reset(_("Cancel"));
  echo "</p>";

  echo "</form>\n";
}

}

?>
