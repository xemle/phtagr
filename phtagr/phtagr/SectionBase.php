<?php

class SectionBase
{

var $name;
var $subsections;

function SectionBase($name='default')
{
    $this->name=$name;
    $this->subsections=array();
}
    
function div_open($css_class)
{
    echo "<div class=\"$css_class\">";
}

function div_close()
{
    echo "</div>\n";
}

function add_section($section) 
{
    array_push($this->subsections, $section);
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

/** Add paragraph section */
function p($text)
{
    echo "<p>".$text."</p>\n";
}

function print_content()
{
    // add dummy text
    echo "&nbsp;\n";
}

function print_section()
{
    $this->div_open($this->name);
    if (count($this->subsections))
    { 
        foreach ($this->subsections as $sub)
        {
            $sub->print_section();
        }
    }
    $this->print_content();
    $this->div_close();
    echo "\n";
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

}
?>
