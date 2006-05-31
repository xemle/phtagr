<?php

/** Base class of all classes. It provides basic wrappers for HTML output.
@class Base */
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

}
?>
