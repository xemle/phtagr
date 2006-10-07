<?php

include_once("$phtagr_lib/Base.php");

define('PARAM_UNKNOWN', 0x00);
define('PARAM_INT', 0x01);
define('PARAM_PINT', 0x02);
define('PARAM_STRING', 0x03);

/**
  @class Url Abstracts a URL with parameters and anchors
*/
class Url extends Base
{

var $base;
var $params;
var $anchor;

function Url($base='')
{
  if ($base!='')
    $this->base=$base;
  else 
    $this->base="index.php";

  $this->anchor='';
  $this->init_params();
}

/** @return Returns the base link */
function get_base()
{
  return $this->base;
}

/** @param base New base link without parameters
  @return True on success, false otherwise */
function set_base($base)
{
  if ($base=='' || $base==null)
    return false;
  $this->base=$base;
  return true;
}

/** Resets all parameters */
function clear_params()
{
  unset($this->params);
  $this->params=array();
}

/** Initiate the basic parameters. If no cookie is set the PHP Session ID is
 * added as parameter */
function init_params()
{
  $this->clear_params();
  if ($_SESSION['withcookie']!=true)
    $this->add_param('PHPSESSID', session_id());
}

/** Returns a parameter 
  @param name Name of the parameter 
  @param default Default value, if parameter is not set
  @return Returns default if the parameter is not available */
function get_param($name, $default=null)
{
  if (isset($this->params[$name]))
    return $this->params[$name];
  return $default;
}

/** Add a parameter. An existing parameter will be overwritten.
  @param name Name of the parameter
  @param value Value of the parameter 
  @param check Type of value checks. If the value does not match the check, the
  default value is taken. There is no check for the default value. If the final
  value is null, the parameter is removed from the list. Possible values are
  PARAM_UNKNOWN, PARAM_INT, PARAM_UINT, PARAM_STRING. There is no check for
  PARAM_UNKNOWN. PARAM_PINT is a positive integer Default is PARAM_UNKNOWN.
  @param default Default value, if the value does not match the type check.
  @return True on success, false otherwise */
function add_param($name, $value, $check=PARAM_UNKNOWN, $default=null)
{
  if ($name==null || $name=='')
    return false;
  
  switch($check) {
  case PARAM_INT:
    if (!is_numeric($value))
      $value=$default;
    break;
  case PARAM_PINT:
    if (!is_numeric($value) || $value <= 0)
      $value=$default;
    break;
  case PARAM_STRING:
    if (!is_string($value))
      $value=$default;
    break;
  default:
    break;
  }

  if ($value==null || $value==='')
  {
    $this->rem_param($name);
    return false;
  }
  
  $this->params[$name]=$value;
  return true;
}

/** Like add_param, but uses $_REQUEST as name and value */
function add_rparam($name, $check=PARAM_UNKNOWN, $default=null)
{
  return $this->add_param($name, $_REQUEST[$name], $check, $default);
}

/** Add an integer paramter with lower and upper bounds
  @param name Name of the parameter
  @param value Value of the parameter
  @param default Default value if the bounds are not match. If default value is
  null, the parameter will be removed.
  @param lbound Valid lower bound of the integer. Default is 0.
  @param ubound Valid upper bound of the integer. If null, no upper bound will
  be checked. Default is null. 
  @return True on success, false otherwise */
function add_iparam($name, $value, $default, $lbound=0, $ubound=null)
{
  if ($name==null || $name=='')
    return false;

  if ($value===null || !is_numeric($value))
    $value=$default;

  if ($value < $lbound || ($ubound!=null && $value > $ubound))
    $value=$default;

  if ($value===null)
    return $this->rem_param($name);

  $this->params[$name]=$value;
  return true;
}

/** Removes a parameter
  @param name Name of the parameter
  @return True on success. False otherwise. */
function rem_param($name)
{
  if ($name==null || $name=='')
    return false;
  
  if (isset($this->params[$name]))
    unset($this->params[$name]);
  return true;
}

/** @return Returns the anchor */
function get_anchor()
{
  return $this->anchor;
}

/** Sets a new anchor. An existing one will be overwritten 
  @param name New anchor name 
  @return True on success, false otherwise */
function set_anchor($name)
{
  if ($name==null || $name=='')
    return false;
  $this->anchor=$name; 
}

/** Removes the anchor */
function rem_anchor()
{
  $this->anchor='';
}

/** Creates a the link from the URL. */
function from_URL()
{
  $this->add_rparam('section');
}

/** Returns the link as URL string */
function to_URL()
{
  $url=$this->base;
  $n=count($this->params);
  if ($n>0)
  {
    $url.='?';
    $i=0;
    foreach ($this->params as $p => $v)
    {
      $i++;
      $url.=$p.'='.$v;
      if ($i<$n)
        $url.='&amp;';
    }
  }
  if ($this->anchor!='')
    $url.='#'.$this->anchor;

  return $url;
}

/** Print a hidden input form 
  @param name name of the hidden parameter
  @param value value of the parameter */
function _input($name, $value) 
{
  return "<input type=\"hidden\" name=\"$name\" value=\"$value\" />\n";
}

/** Returns a string of hidden form inputs */
function to_form()
{
  $input='';
  foreach ($this->params as $p => $v)
    $input.=$this->_input($p, $v);
  return $input;
}

}

?>
