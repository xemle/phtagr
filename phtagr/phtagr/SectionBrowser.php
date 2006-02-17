<?php

global $prefix;
include_once("$prefix/SectionBody.php");

class SectionBrowser extends SectionBody
{

var $root;
var $path;
var $images;

function SectionBrowser()
{
    $this->name="browser";
    $this->root='';
    $this->path=getcwd();
    $this->images=array();
}

function is_dir2($dir)
{
    if (is_dir("$this->root/$dir"))
    {   
        return true;
    } else {
        return false;
    }
}

function chdir2($dir)
{
    if ($this->is_dir2($dir)) 
    {
        chdir("$this->root/$dir");
    } else {
        chdir("$this->root");
    }
}

function opendir2($dir)
{
    return opendir("$this->root/$dir");
}

function is_readable2($dir)
{
    return is_readable("$this->root/$dir");
}

function find_images($dir)
{
    $subdirs=array();
    
    if (!$this->is_readable2($dir) || !$this->is_dir2($dir)) return;
    $this->chdir2($dir);
    if (!$handle = $this->opendir2($dir)) return;
    
    while (false != ($file = readdir($handle))) {
        if (!is_readable($file) || $file=='.' || $file=='..') continue;

        $file="$dir/$file";
        if ($this->is_dir2($file)) {
            array_push($subdirs, "$file");
        } else if (strtolower(substr($file, -3, 3))=='jpg') {
            array_push($this->images, "$file");
        }
    }
    closedir($handle);
    
    foreach ($subdirs as $sub) {
        $this->find_images($sub);
    }
}

function print_browser($dir)
{
    if (!$this->is_readable2($dir) || !$this->is_dir2($dir)) return;
    $this->chdir2($dir);
    if (!$handle = $this->opendir2($dir)) return;
    
    $subdirs=array();
    
    echo "Path:&nbsp;";
    $dirs=split('/', $dir);
    echo "<a href=\"?section=browser&cd=/\">root</a>";
    $path='';
    foreach ($dirs as $cd)
    {
        if ($cd == '' || $path == '/') continue;

        $path = "$path/$cd";
        echo "&nbsp;/&nbsp;";
        
        if ($this->is_dir2($path)) {
            echo "<a href=\"?section=browser&cd=$path\">$cd</a>";
        } else {
            echo "$cd";
        }
    }
    echo "&nbsp;/&nbsp;";
    
    $handle=$this->opendir2($dir);
    while (false != ($file = readdir($handle))) {
        if (!is_readable($file) || $file=='.' || $file=='..') continue;
        if (substr($file, 0, 1)=='.') continue; 

        if ($this->is_dir2("$dir/$file")) {
            array_push($subdirs, "$file");
        }
    }
    closedir($handle);

    echo "<form section=\"index.php\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"section\" value=\"browser\" />";

    asort($subdirs);
    echo "<input type=\"checkbox\" name=\"add[]\" value=\"$dir\" />&nbsp;. (this dir)</br>\n";
    foreach($subdirs as $sub) 
    {
        if ($dir != '/') {
            $cd="$dir/$sub";
        } else {
            $cd=$sub;
        }
        echo "<input type=\"checkbox\" name=\"add[]\" value=\"$cd\" />&nbsp;<a href=\"?section=browser&cd=$cd\">$sub</a></br>\n";
    }
    echo "<input type=\"submit\" value=\"Add images\" />";
    echo "<input type=\"reset\" value=\"Clear\" />";
    
    echo "<form>\n";
}

function print_content()
{
    global $auth; 
    echo "<h2>Browser</h2>\n";
    if (isset($_REQUEST['add'])) {
        foreach ($_REQUEST['add'] as $d)
        {
            $this->find_images($d);
        }
        if (count($this->images))
        { 
            asort($this->images);
        }
        printf ("Found %d images<br/>\n", count($this->images));
        foreach ($this->images as $img)
        {
            $return=update_file($auth->userid, $this->root . $img);
            if ($return==1)
              echo "Image '$img' was updated.<br/>\n";
        }
        echo "<a href=\"index.php?section=browser&cd=$this->path\">Search again</a><br/>\n";
    } else if (isset($_REQUEST['cd'])) 
    {
        $this->path=$_REQUEST['cd'];
        $this->print_browser($this->path);
    } else {
        $this->print_browser($this->path);
    }

    //echo '<pre>'; print_r($_REQUEST); echo '</pre>';
}

}

?>
