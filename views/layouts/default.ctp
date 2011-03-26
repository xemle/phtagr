<?php echo $html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout?></title>
<?php 
  echo $html->charset('UTF-8')."\n";
  echo $html->meta('icon')."\n";
  echo $html->css('default')."\n";
  echo $html->script('jquery-1.5.min');
  // jquery ui
  echo $html->css('custom-phtagr/jquery-ui-1.8.9.custom');
  echo $html->script('jquery-ui-1.8.9.custom.min');

  echo $html->script('jquery-phtagr');
  echo $scripts_for_layout; 
?>
</head>

<body><div id="page">

<div id="header"><div class="sub">
<h1><?php echo $option->get('general.title', 'phTagr.'); ?></h1>
<span class="subtitle"><?php echo $option->get('general.subtitle', 'Social Web Gallery'); ?></span>
<?php echo $menu->menu('top-menu'); ?>
</div></div><!-- #header/sub -->

<div id="main-menu"><div class="sub">
<ul>
  <li><?php echo $html->link('Home', '/'); ?></li>
  <li><?php echo $html->link('Explorer', '/explorer'); ?></li>
  <li><?php echo $html->link('My Photos', '/explorer'); ?></li>
  <li><?php echo $html->link('Upload', '/browser/quickupload'); ?></li>
</ul>
</div></div><!-- #main-menu/sub -->

<div id="main"><div class="sub">

<div id="content"><div class="sub">
<?php echo $content_for_layout?>
</div></div><!-- #content/sub -->

</div></div><!-- #main/sub -->

<div id="footer"><div class="sub">
<p>&copy; 2006-2011 by <?php echo $html->link("Open Source Social Web Gallery phTagr", 'http://www.phtagr.org'); ?></p>
</div></div><!-- #footer/sub -->

</div></body><!-- #page -->
</html>
