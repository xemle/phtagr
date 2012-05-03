<?php echo $this->Html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout?></title>
<?php
  echo $this->Html->charset('UTF-8')."\n";
  echo $this->Html->meta('icon')."\n";
  echo $this->Html->css('phtagr')."\n";
  echo $this->Html->script(array('prototype', 'event-selectors', 'effects', 'controls', 'phtagr'))."\n";

  echo $scripts_for_layout;
  echo $feeds_for_layout;
?>

<!--[if lte IE 7]>
<?php echo $this->Html->css('patches/patch_phtagr')."\n"; ?>
<![endif]-->
</head>

<body>
<div id="page_margins">
<div id="border-top">
  <div id="edge-tl"></div>
  <div id="edge-tr"></div>
</div>
<div id="page">

<div id="header">
<div id="topnav">
<a class="skip" href="#navigation" title="skip link">Skip to the navigation</a><span class="hideme">.</span>
<a class="skip" href="#main_content" title="skip link">Skip to the content</a><span class="hideme">.</span>
<?php echo View::element('topnav'); ?>
</div>
<?php echo View::element('header'); ?>
</div><!-- header -->

<div id="nav">
<a id="navigation" name="navigation"></a>
<div id="nav_main">
<?php echo View::element('menu'); ?>
</div>
</div>

<div id="main">

<div id="col1">
<div id="col1_content" class="clearfix">
<?php echo View::element('main_menu'); ?>
</div>
</div>

<div id="col3">
<div id="col3_content" class="clearfix">
<div id="spinner" style="display: none; float: right; ">
  <?php echo $this->Html->image('spinner.gif'); ?>
</div>
<div id="main_content">
<?php echo $content_for_layout?>
</div>
<div id="ie_clearing">&nbsp;</div>
</div>
</div>

</div><!-- main -->

<div id="footer">
<?php echo View::element('footer'); ?>
</div><!-- footer -->
</div><!-- page -->

<div id="border-bottom">
  <div id="edge-bl"></div>
  <div id="edge-br"></div>
</div>
</div><!-- page margins -->
</body>
</html>
