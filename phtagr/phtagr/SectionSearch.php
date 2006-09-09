<?php

global $prefix;
global $db;

include_once("$phtagr_prefix/SectionBase.php");
include_once("$phtagr_prefix/Search.php");
include_once("$phtagr_prefix/Edit.php");
include_once("$phtagr_prefix/Sql.php");


class SectionSearch extends SectionBase
{

function SectionSearch()
{
  $this->name="search";
}

function print_content()
{
  echo "<h2>Advanced Search</h2>
  
<form action=\"index.php\" method=\"post\">
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
      after: <input type=\"text\" name=\"start\" size=\"10\" /><br />
      before: <input type=\"text\" name=\"end\" size=\"10\" /><br />
      E.g. \"2006-04-21\"
    </td>
  </tr>
  <tr>
    <th>Location:</th>
    <td>
      <input type=\"text\" name=\"location\" size=\"30\" /><br />
      Type: <select name=\"location_type\" size=\"1\">
        <option value=\"".LOCATION_UNDEFINED."\" selected=\"selected\">undefined</option>
        <option value=\"".LOCATION_CITY."\">City</option>
        <option value=\"".LOCATION_SUBLOCATION."\">Sublocation</option>
        <option value=\"".LOCATION_STATE."\">State</option>
        <option value=\"".LOCATION_COUNTRY."\">Country</option>
      </select>
    </td>
  <tr>
    <th>Sort by:</th>
    <td>
      <select name=\"orderby\">
        <option value=\"date\" selected=\"selected\">date</option>
        <option value=\"-date\">date asc</option>
        <option value=\"ranking\">ranking</option>
        <option value=\"-ranking\">ranking asc</option>
        <option value=\"voting\">voting</option>
        <option value=\"-voting\">voting asc</option>
        <option value=\"newest\">newest</option>
        <option value=\"-newest\">newest desc</option>
      </select>
    </td>
  </tr>
  <tr>
    <th>Page size:</th>
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
    <td><input type=\"submit\" value=\" Search \" />
        <input type=\"reset\" value=\" Cancel \" /></td>
  </tr>
</table>
</form>
";
}


}

?>
