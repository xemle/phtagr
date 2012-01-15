<h1><?php __("Database upgrade"); ?></h1>

<?php echo $this->Session->flash(); ?>

<div class="info">
<?php __("The database schema requires an upgrade. Do you want to perform the upgrade?"); ?>
</div>

<?php echo $this->Html->link(__("Upgrade", true), '/admin/setup/upgrade/run'); ?> or 
<?php echo $this->Html->link(__("Cancel", true), '/'); ?>

