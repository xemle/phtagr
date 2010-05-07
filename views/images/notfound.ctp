<h1><?php __("No media Found"); ?></h1>
<?php $session->flash(); ?>
<div class="info"><?php printf(__("No media found. Please goto the %s and try again", true), $html->link(__("Explorer", true), '/explorer')); ?></div>
