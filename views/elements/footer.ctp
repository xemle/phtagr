<?php 
global $TIME_START; 
$time = getMicrotime() - $TIME_START;
printf(__("%s Social Web Gallery %s in %.3fs. Layout based on %s, Icons by %s", true), '&copy; 2006-2010',  $html->link('phtagr.org', 'http://www.phtagr.org'),  $time, $html->link('YAML', 'http://www.yaml.de'), $html->link('FamFamFam', 'http://www.famfamfam.com')); 
?>
