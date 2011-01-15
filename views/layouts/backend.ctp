<?php echo $html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout?></title>
<?php 
  echo $html->charset('UTF-8')."\n";
  echo $html->meta('icon')."\n";
  echo $html->css('backend')."\n";
  echo $javascript->link('phtagr');
  echo $scripts_for_layout; 
?>

</head>

<body>
<div id="page">

<div id="header"><div class="sub">
<?php echo $html->link(__('Gallery', true), '/'); ?>
</div></div>

<div id="main">

<div id="sidebar">
<div class="box">
<h1>Menu</h1>
<?php echo $menu->menu('main'); ?>
</div>
</div>
<div id="content">
<?php echo $content_for_layout?>
</div>
</div><!-- main -->

<div id="footer"><div class="sub">
<p>This is the very cool footer</p>
</div></div>
</body>
</html>
