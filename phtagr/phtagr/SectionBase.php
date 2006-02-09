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
    
function print_span($css_class, $text)
{
    return "<span class=\"$css_class\">$text</span>";
}

function print_div($css_class, $text)
{
    return "<div class=\"$css_class\">$text</div>\n";
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

function print_content()
{
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

function print_warning($message)
{
    $this->div_open('warning');
    echo $message;
    $this->div_close();
}

function print_error($message)
{
    $this->div_open('error');
    echo $message;
    $this->div_close();
}

function print_success($message)
{
    $this->div_open('success');
    echo $message;
    $this->div_close();
}

}
?>
