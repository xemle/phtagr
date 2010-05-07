<h1><?php __("Database upgrade"); ?></h1>

<?php $session->flash(); ?>

<div class="info">
<?php __("The database schema requires an upgrade. Do you want to perform the upgrade?"); ?>
</div>

<?php echo $html->link(__("Upgrade", true), '/admin/setup/upgrade/run'); ?> or 
<?php echo $html->link(__("Cancel", true), '/'); ?>

