<?php

/** Base class of all classes. It provides basic wrappers for HTML output.
@class Base */
class Base
{

function div_open($css_class)
{
  echo "<div class=\"$css_class\">";
}

function div_close()
{
  echo "</div>\n";
}

/** Add span section */
function span($css_class, $text)
{
  echo "<span class=\"$css_class\">".$text."</span>";
}

/** Add div section */
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

function comment($message)
{
  echo "<-- $message -->\n";
}

}
?>
