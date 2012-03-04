<h1><?php echo __("Database upgrade"); ?></h1>

<?php echo $this->Session->flash(); ?>

<div class="info">
<?php echo __("Your database is up-to-date."); ?>
</div>

<?php echo $this->Html->link(__("Continue"), '/');
