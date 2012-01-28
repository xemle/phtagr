<h1><?php __('Advanced Search'); ?></h1>

<?php echo $form->create(null, array('action' => 'query')); ?>
<fieldset><legend><?php __('Metadata'); ?></legend>
<?php 
  echo $form->input('Media.tags', array('label' => __('Tags', true), 'after' => '<span class="hint">' . __('E.g. includeTag, -excludeTag', true) . '</span>'));
  echo $form->input('Media.categories', array('label' => __('Categories', true)));
  echo $form->input('Media.locations', array('label' => __('Locations', true)));

  echo $form->input('Media.from', array('label' => __('Date from', true), 'after' => '<span class="hint">' . __('E.g. 2008-08-07', true) . '</span>'));
  echo $form->input('Media.to', array('label' => __('Date to', true)));
?>
</fieldset>
<fieldset><legend><?php __('Options'); ?></legend>
<?php 
  $pages = array(6 => 6, 12 => 12, 24 => 24, 60 => 60, 120 => 120, 240 => 240);
  echo $form->input('Option.show', array('type' => 'select', 'options' => $pages, 'selected' => 12, 'label' => __('Page size', true)));

  $order = array('default' => __('Default', true), 'date' => __('Date', true), 'newest' => __('Newest', true), 'changes' => __('Changes', true), 'random' => __('Random', true), 'popularity' => __('Popularity', true));
  echo $form->input('Option.sort', array('type' => 'select', 'options' => $order, 'selected' => 'default', 'label' => __('Sort by', true)));
?>
</fieldset>
<?php if ($userRole >= ROLE_GUEST): ?>
<fieldset><legend><?php __('Advanced'); ?></legend>
<?php
  if ($userId) {
    echo $form->hidden('User.username', array('value' => $userId));
  }
  if ($userRole >= ROLE_GUEST) {
    echo $form->input('Media.name', array('label' => __('Name', true)));
    $type = array('any' => __('Any Type', true), 'image' => __('Image or Photos', true), 'video' => __('Video', true));
    echo $form->input('Media.type', array('type' => 'select', 'options' => $type, 'selected' => 'any', 'label' => __('File Type', true)));
  }
  if ($userRole >= ROLE_USER) {
    if (!$userId) {
      echo $form->input('User.username', array('label' => __('Username', true)));
    }
    echo $form->input('Group.name', array('type' => 'select', 'options' => $groups, 'selected' => -1, 'label' => __('Group', true)));

    $visibility = array('any' => __('Any', true), 'private' => __('Private', true), 'group' => __('Group members', true), 'user' => __('User', true), 'public' => __('Public', true));
    echo $form->input('Media.visibility', array('type' => 'select', 'options' => $visibility, 'label' => __('Media visibility', true)));
  }

  $op = array(0 => 'default', 'AND' => __('AND', true), 'OR' => __('OR', true));
  echo $form->input('Media.operand', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('General Operand', true)));
  echo $form->input('Media.tag_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Tag operand', true)));
  echo $form->input('Media.category_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Category operand', true)));
  echo $form->input('Media.location_op', array('type' => 'select', 'options' => $op, 'selected' => 'default', 'label' => __('Location operand', true)));

?>
</fieldset>
<?php endif; ?>
<?php echo $form->end(__('Search', true)); ?>
