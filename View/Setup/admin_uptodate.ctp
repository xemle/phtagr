<h1><?php __("Database upgrade"); ?></h1>

<?php echo $session->flash(); ?>

<div class="info">
<?php __("Your database is up-to-date."); ?>
</div>

<?php echo $html->link(__("Continue", true), '/');
