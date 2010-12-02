<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
<?php echo $session->flash(); ?>

<h1>Search Group</h1>

<h2><?php __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $form->create(null, array('action' => 'search')); ?>
<?php 
  echo $form->input('Group.searchquery', array('label' => __('Find Group:', true)));
?>
<?php echo $form->end(__('Search', true)); ?>

</ul>
<?php
debug($this->data);

?>
