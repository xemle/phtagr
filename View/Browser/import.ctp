<h1><?php echo __("Browser"); ?></h1>

<?php echo $this->Session->flash(); ?>

<p><?php echo __("View folder %s", $this->Html->link($path, "index/$path")); ?></p>
