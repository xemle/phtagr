<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
<h1>Group Memberships</h1>

<?php echo $this->Session->flash(); ?>

<table class="default">
<thead>
<?php
$headers = array(__('Group Name'), __('Action'));
echo $this->Html->tableHeaders($headers);
?>
</thead>
<tbody>

<?php
$allgroup_names = Set::extract('/Member/name', $this->request->data);
sort($allgroup_names);
$cells = array();
foreach ($allgroup_names as $group_name) {
	$row = array($this->Html->link($group_name, "/groups/view/{$group_name}"), $this->Html->link("unsubscribe", "/groups/unsubscribe/{$group_name}"));
	$cells[] = $row;
}

echo $this->Html->tableCells($cells, array('class' => 'odd'), array('class' => 'even'));
?>

</tbody>
</table>

<?php
debug($this->request->data);

?>
