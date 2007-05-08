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

include_once("$phtagr_lib/Constants.php");

/** @class Base 
  Base class of all classes. It provides basic wrappers for HTML output. */
class Base
{

function div_open($css_class)
{
  echo "<div class=\"$css_class\">\n";
}

/** Prints a closed div tag
  @param newline Prints no newline after the tag if false. Default is true
  */
function div_close($newline=true)
{
  if ($newline)
    echo "</div>\n";
  else
    echo "</div>";
}

/** Add span section without a newline after the tag */
function span($css_class, $text)
{
  echo "<span class=\"$css_class\">".$text."</span>";
}

/** Add div section with an newline after the tag */
function div($css_class, $text)
{
  echo "<div class=\"$css_class\">".$text."</div>\n";
}

/** Escape the string for html output 
  @param str String to escape
  @param return If true, print the output directly. Default is true
  @return Escaped string */
function escape_html($str, $return=true)
{
  $esc=htmlentities($str, ENT_QUOTES, "UTF-8");
  if ($return) 
    return $esc;

  echo $esc;
  return "";
}

function warning($message)
{
  $this->div('warning', $message);
}

function error($message)
{
  $this->div('error', $message);
}

function info($message)
{
  $this->div('info', $message);
}

function success($message)
{
  $this->div('success', $message);
}

function question($message)
{
  $this->div('question', $message);
}

function comment($message)
{
  echo "<!-- $message -->\n";
}

/** Prints an object by print_r. This function should be used for debug only 
  @param object Object which is dumped */
function debug($object)
{
  $this->div_open("debug");
  echo "<pre>";
  print_r($object);
  echo "</pre>";
  $this->div_close();
}

function debug_buf($buf, $len)
{
  $c=0;
  while ($c<$len) {
    $c++;
    if ($c%4==0) echo " ";
    if ($c%8==0) echo " ";
    if ($c%16==0) echo "\n";
    
    printf("%2x ", $buf{$c-1});
  }
  if (!($c%16==0))
    echo "\n";
}

}
?>
