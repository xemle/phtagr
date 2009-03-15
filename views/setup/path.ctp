<?php $session->flash(); ?>
<h1>Paths</h1>

<?php if (count($missing)): ?>
<div class="error">
Following paths are missing. Please create these paths!
</div>

<pre>
<?php 
foreach($missing as $path) 
  echo $path."\n";
?>
</pre>
<?php endif; ?>

<?php if (count($readonly)): ?>
<div class="error">
Following paths are not writeable. Please change the permissions!
</div>

<pre>
<?php 
foreach($readonly as $path) 
  echo $path."\n";
?>
</pre>
<?php endif; ?>

<?php echo $html->link('Retry', 'path'); ?>
