<?php

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
  echo $url->get_form();
  echo "<table>
  <tr>
    <th>"._("Tags:")."</th>
    <td>
      <input type=\"text\" name=\"tags\" size=\"30\" /><br />
      Operation: <input type=\"radio\" name=\"tagop\" value=\"0\" checked /> AND,  
      <input type=\"radio\" name=\"tagop\" value=\"1\" /> OR,  
      <input type=\"radio\" name=\"tagop\" value=\"2\" /> FUZZY
    </td>
  </tr>
  <tr>
    <th>"._("Sets:")."</th>
    <td>
      <input type=\"text\" name=\"sets\" size=\"30\" /><br />
      Operation: <input type=\"radio\" name=\"setop\" value=\"0\" checked /> AND,  
      <input type=\"radio\" name=\"setop\" value=\"1\" /> OR,  
      <input type=\"radio\" name=\"setop\" value=\"2\" /> FUZZY
    </td>
  </tr>
  <tr>
    <th>"._("Date:")."</th>
    <td>
      "._("after:")." <input type=\"text\" name=\"start\" size=\"10\" /><br />
      "._("before:")." <input type=\"text\" name=\"end\" size=\"10\" /><br />
      "._("E.g.")." \"2006-04-21\"
    </td>
  </tr>
  <tr>
    <th>"._("Location:")."</th>
    <td>
      <input type=\"text\" name=\"location\" size=\"30\" /><br />
      Type: <select name=\"location_type\" size=\"1\">
        <option value=\"".LOCATION_UNDEFINED."\" selected=\"selected\">"._("undefined")."</option>
        <option value=\"".LOCATION_CITY."\">"._("City")."</option>
        <option value=\"".LOCATION_SUBLOCATION."\">"._("Sublocation")."</option>
        <option value=\"".LOCATION_STATE."\">"._("State")."</option>
        <option value=\"".LOCATION_COUNTRY."\">"._("Country")."</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>"._("User:")."</th>
    <td><input type=\"text\" name=\"user\"/></td>
  </tr>\n";
  if ($user->is_member())
  {
    $groups=$user->get_groups();
    if (count($groups)>0)
    {
      echo "  <tr>
    <th>"._("Groups:")."</td>
    <td>
      <select size=\"1\" name=\"group\">
        <option value=\"\" selected=\"selected\">"._("None")."</option>\n";
      foreach ($groups as $gid => $name)
        echo "        <option value=\"$gid\">$name</option>\n";
      echo "        <option value=\"0\">"._("Not assigned")."</option>\n";
      echo "      </select>
    </td>
  </tr>\n";
    }
    echo "   <tr>
    <th>"._("Visibility:")."</th>
    <td>
      <select size=\"1\" name=\"visibility\">
        <option value=\"\" selected=\"selected\">"._("None")."</option>
        <option value=\"group\">"._("Group")."</option>
        <option value=\"member\">"._("Member")."</option>
        <option value=\"public\">"._("Public")."</option>
      </select>
    </td>\n";
  }
  echo "  <tr>
    <th>"._("Sort by:")."</th>
    <td>
      <select name=\"orderby\">
        <option value=\"date\" selected=\"selected\">"._("date")."</option>
        <option value=\"-date\">"._("date asc")."</option>
        <option value=\"popularity\">"._("Popularity")."</option>
        <option value=\"-popularity\">"._("Popularity asc")."</option>
        <option value=\"voting\">"._("Voting")."</option>
        <option value=\"-voting\">"._("Voting asc")."</option>
        <option value=\"newest\">"._("Newest")."</option>
        <option value=\"-newest\">"._("Newest desc")."</option>
        <option value=\"changes\">"._("Changes")."</option>
        <option value=\"-changes\">"._("Changes desc")."</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>"._("Page size:")."</th>
    <td>
      <select name=\"pagesize\">
        <option value=\"10\" selected=\"selected\">10</option>
        <option value=\"20\">20</option>
        <option value=\"50\">50</option>
        <option value=\"100\">100</option>
        <option value=\"200\">200</option>
      </select>
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input type=\"submit\" class=\"submit\" value=\""._("Search")."\" />
        <input type=\"reset\" class=\"reset\" value=\""._("Cancel")."\" /></td>
  </tr>
</table>
</form>
";
}

}

?>
