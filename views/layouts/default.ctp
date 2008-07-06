<?php echo $html->docType('xhtml-strict'); ?>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title><?php echo $title_for_layout?></title>
<?php 
  echo $html->charset('UTF-8')."\n";
  echo $html->meta('icon')."\n";
  echo $html->css('phtagr')."\n";
  echo $javascript->link(array('prototype', 'event-selectors', 'effects', 'controls', 'phtagr'))."\n";
  
  if (!empty($feeds)) {
    if (!is_array($feeds))
      $feeds = array($feeds);
    foreach ($feeds as $feed) {
      echo $html->meta('rss', $feed);
    }
  }
 
?>

<!--[if lte IE 7]>
<?php echo $html->css('patches/patch_phtagr')."\n"; ?>
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
  <?php echo $html->image('spinner.gif'); ?>
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
