<h1><?php echo __('Advanced Search'); ?></h1>

<?php echo $this->Form->create(null, array('url' => 'query')); ?>
<fieldset><legend><?php echo __('Metadata'); ?></legend>
<?php
  echo $this->Form->input('Media.tags', array('label' => __('Tags'), 'after' => '<span class="hint">' . __('E.g. includeTag, -excludeTag') . '</span>'));
  echo $this->Form->input('Media.categories', array('label' => __('Categories')));
  echo $this->Form->input('Media.locations', array('label' => __('Locations')));

  echo $this->Form->input('Media.from', array('label' => __('Date from'), 'after' => '<span class="hint">' . __('E.g. 2008-08-07') . '</span>'));
  echo $this->Form->input('Media.to', array('label' => __('Date to')));
?>
</fieldset>
<fieldset><legend><?php echo __('Options'); ?></legend>
<?php
  $pages = array(6 => 6, 12 => 12, 24 => 24, 60 => 60, 120 => 120, 240 => 240);
  echo $this->Form->input('Option.show', array('type' => 'select', 'options' => $pages, 'selected' => 12, 'label' => __('Page size')));

  $order = array('default' => __('Default', true), 'date' => __('Date', true), 'newest' => __('Newest', true), 'changes' => __('Changes', true), 'random' => __('Random'), 'popularity' => __('Popularity'));
  echo $this->Form->input('Option.sort', array('type' => 'select', 'options' => $order, 'selected' => 'default', 'label' => __('Sort by')));
?>
</fieldset>
<?php if ($userRole >= ROLE_GUEST): ?>
<fieldset><legend><?php echo __('Advanced'); ?></legend>
<?php
  if ($userId) {
    echo $this->Form->hidden('User.username', array('value' => $userId));
  }
  if ($userRole >= ROLE_GUEST) {
    echo $this->Form->input('Media.name', array('label' => __('Name')));
    $type = array('any' => __('Any Type', true), 'image' => __('Image or Photos'), 'video' => __('Video'));
    echo $this->Form->input('Media.type', array('type' => 'select', 'options' => $type, 'selected' => 'any', 'label' => __('File Type')));
  }
  if ($userRole >= ROLE_USER) {
    if (!$userId) {
      echo $this->Form->input('User.username', array('label' => __('Username')));
    }
    echo $this->Form->input('Group.name', array('type' => 'select', 'options' => $groups, 'selected' => -1, 'label' => __('Group')));

    $visibility = array('any' => __('Any', true), 'private' => __('Private', true), 'group' => __('Group members', true), 'user' => __('User'), 'public' => __('Public'));
    echo $this->Form->input('Media.visibility', array('type' => 'select', 'options' => $visibility, 'label' => __('Media visibility')));
  }

  $op = array(0 => 'default', 'AND' => __('AND'), 'OR' => __('OR'));
  echo $this->Form->input('Media.operand', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('General Operand')));
  echo $this->Form->input('Media.tag_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Tag operand')));
  echo $this->Form->input('Media.category_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Category operand')));
  echo $this->Form->input('Media.location_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Location operand')));

?>
</fieldset>
<?php endif; ?>
<?php echo $this->Form->end(__('Search')); ?>
