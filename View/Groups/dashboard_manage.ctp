<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
<h1>Group Memberships</h1>

<?php echo $session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name', true), __('Action', true));
echo $html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php 
$allgroup_names = Set::extract('/Member/name', $this->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($html->link($group_name, "/groups/view/{$group_name}"), $html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->data);

?>
