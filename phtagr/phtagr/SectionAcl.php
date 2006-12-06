<?php

include_once("$phtagr_lib/SectionBase.php");

class SectionAcl extends SectionBase
{

var $_acl;

function SectionAcl($gacl=0, $macl=0, $aacl=0)
{
  $this->name="acl";
  $this->_acl=array(ACL_GROUP => $gacl, 
                ACL_MEMBER => $macl, 
                ACL_ANY => $aacl);
}

function _print_row_read($keep)
{
  echo "  <tr>
    <td>"._("Read level")."</td>\n";

  $levels=array(ACL_PREVIEW);

  for ($i=ACL_GROUP ; $i<=ACL_ANY ; $i++)
  {
    switch ($i)
    {
    case ACL_GROUP:
      $prefix='g';
      $value=$this->_acl[ACL_GROUP] & ACL_READ_MASK;
      break;
    case ACL_MEMBER:
      $prefix='m';
      $value=$this->_acl[ACL_MEMBER] & ACL_READ_MASK;
      break;
    case ACL_ANY:
      $prefix='a';
      $value=$this->_acl[ACL_ANY] & ACL_READ_MASK;
      break;
    default:
    }

    echo "    <td>
      <select size=\"1\" name=\"".$prefix."acl_read\">\n";
    if ($keep)
      echo "<option value=\"keep\">"._("Keep")."</option>\n";
    foreach ($levels as $level)
    {
      $select=($value==$level)?"selected=\"selected\" ":"";
      if ($level==ACL_PREVIEW)
        echo "<option value=\"preview\"$select>"._("Preview")."</option>\n";

    }
    $select=($value==0)?"selected=\"selected\" ":"";
    echo "<option value=\"deny\"$select>"._("Deny")."</option>
      </select>
    </td>\n";
  }
  echo "  </tr>\n";
}

function _print_row_write($keep)
{
  echo "  <tr>
    <td>"._("Write level")."</td>\n";

  $levels=array(ACL_EDIT);

  for ($i=ACL_GROUP ; $i<=ACL_ANY ; $i++)
  {
    switch ($i)
    {
    case ACL_GROUP:
      $prefix='g';
      $value=$this->_acl[ACL_GROUP] & ACL_WRITE_MASK;
      break;
    case ACL_MEMBER:
      $prefix='m';
      $value=$this->_acl[ACL_MEMBER] & ACL_WRITE_MASK;
      break;
    case ACL_ANY:
      $prefix='a';
      $value=$this->_acl[ACL_ANY] & ACL_WRITE_MASK;
      break;
    default:
    }

    echo "    <td>
      <select size=\"1\" name=\"".$prefix."acl_write\">\n";
    if ($keep)
      echo "<option value=\"keep\">"._("Keep")."</option>\n";
    foreach ($levels as $level)
    {
      $select=($value==$level)?"selected=\"selected\" ":"";
      if ($level==ACL_EDIT)
        echo "<option value=\"edit\"$select>"._("Edit")."</option>\n";

    }
    $select=($value==0)?"selected=\"selected\" ":"";
    echo "<option value=\"deny\"$select>"._("Deny")."</option>
      </select>
    </td>\n";
  }
  echo "  </tr>\n";
}


/** 
  @param keep If true print also 'keep' option */
function print_table($keep=true)
{
  echo "<table>
  <tr>
    <th></th>
    <th>"._("Friends")."</th>
    <th>"._("Members")."</th>
    <th>"._("All")."</th>
  </tr>\n";
  $this->_print_row_write($keep);
  $this->_print_row_read($keep);
  echo "</table>\n";
}

}
?>
