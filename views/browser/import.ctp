<h1><?php __("Browser"); ?></h1>

<?php echo $session->flash(); ?>

<p><?php printf(__("View folder %s", true), $html->link($path, "index/$path")); ?></p>
