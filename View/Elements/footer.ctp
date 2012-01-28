<?php 
global $TIME_START; 
$time = microtime(true) - $TIME_START;
__("%s Social Web Gallery %s in %.3fs. Layout based on %s, Icons by %s", '&copy; 2006-2010',  $this->Html->link('phtagr.org', 'http://www.phtagr.org'),  $time, $this->Html->link('YAML', 'http://www.yaml.de'), $this->Html->link('FamFamFam', 'http://www.famfamfam.com')); 
?>
