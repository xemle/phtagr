<?php echo $this->Html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout; ?></title>
<meta name = "viewport" content = "initial-scale = 1.0">
<?php
  echo $this->Html->charset('UTF-8');
  echo $this->Html->meta('icon');
  echo $this->Html->css('mobile');
?>
</head>

<body>
<div class="header"><?php echo View::element('header'); ?></div>
<div class="menu"><?php echo View::element('menu'); ?></div>
<div class="content"><?php echo $content_for_layout; ?></div>
<div class="footer"><?php echo View::element('footer'); ?></div>
</body>
</html>
