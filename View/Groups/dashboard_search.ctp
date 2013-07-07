<?php echo $this->Session->flash(); ?>

<h1><?php echo __('Search Group'); ?></h1>

<h2><?php echo __('Results') ?></h2>
<ul>
<?php if(!empty($groups)) {
	// @TODO Put groups into a table where we also have a button to join the group
	foreach ($groups as $group) {
		echo "<li>";
		echo $this->Html->link($group['Group']['name'], "/groups/view/{$group['Group']['name']}");
		echo "</li>";
	}
} else {
	echo "<li>Nothing found</li>";
}
?>
<?php echo $this->Form->create(null, array('url' => 'search')); ?>
<?php
  echo $this->Form->input('Group.searchquery', array('label' => __('Find Group:')));
?>
<?php echo $this->Form->end(__('Search')); ?>

</ul>
