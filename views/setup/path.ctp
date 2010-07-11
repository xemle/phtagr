<h1><?php __("Path settings"); ?></h1>

<?php echo $session->flash(); ?>

<?php if (count($missing)): ?>
<div class="error">
<?php __("Following paths are missing. Please create these paths!"); ?>
</div>

<ul>
<?php foreach($missing as $path): ?>
  <li><?php echo $path; ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if (count($readonly)): ?>
<div class="error">
<?php __("Following paths are not writeable. Please change the permissions!"); ?>
</div>

<ul>
<?php foreach($readonly as $path): ?>
  <li><?php echo $path; ?></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<?php echo $html->link(__('Retry', true), 'path'); ?>
