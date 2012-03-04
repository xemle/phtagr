<h1><?php echo __("No media Found"); ?></h1>
<?php echo $this->Session->flash(); ?>
<div class="info"><?php echo __("No media found. Please goto the %s and try again", $this->Html->link(__("Explorer"), '/explorer')); ?></div>
