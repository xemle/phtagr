<?php $session->flash(); ?>

<h1>Upgrade</h1>

<div class="info">
Phtagr requires an upgrade. Do you want to perform the upgrade?
</div>

<?php echo $html->link("Upgrade", '/admin/setup/upgrade/run'); ?> or 
<?php echo $html->link("Cancel", '/'); ?>

