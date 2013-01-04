<?php echo $this->Html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout?></title>
<?php
  echo $this->Html->charset('UTF-8')."\n";
  echo $this->Html->meta('icon')."\n";
  echo $this->Html->css('backend')."\n";
  echo $this->Html->script('jquery-1.5.1.min');
  // jquery ui
  echo $this->Html->css('custom-phtagr/jquery-ui-1.8.14.custom');
  echo $this->Html->script('jquery-ui-1.8.14.custom.min');

  echo $this->Html->script('jquery-phtagr');
  echo $this->Html->script('phtagr');
  echo $scripts_for_layout;
?>

</head>

<body>
<div id="page">

<div id="header"><div class="sub">
<h1><?php echo $this->Html->link($this->Option->get('general.title', 'phTagr.'), '/'); ?></h1>
<span><?php echo $this->Option->get('general.subtitle', 'Tag, Browse, and Share Your Photos'); ?></span>
</div></div>

<div id="main">

<div id="sidebar">
<div class="box">
<h1>Menu</h1>
<?php echo $this->Menu->menu('main'); ?>
</div>
</div>
<div id="content">
<?php echo $content_for_layout?>
</div>
</div><!-- main -->

<div id="footer"><div class="sub">
<p><?php echo __("&copy; 2006-2013 by %s - Tag, Browse, and Share Your Photos.", $this->Html->link("phTagr.org", 'http://www.phtagr.org')) . ' ' . __("You are running version %s.", Configure::read('Phtagr.version')); ?></p>
</div></div>
</body>
</html>
