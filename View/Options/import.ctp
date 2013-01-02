<h1><?php echo __('Import Options'); ?></h1>
<?php echo $this->Session->flash(); ?>
<?php echo $this->Form->create(null, array('action' => 'import')); ?>

<fieldset><legend><?php echo __('Default Access Rights'); ?></legend>
<p><?php echo __('The following access rights are applied to new images.'); ?></p>
<?php
  $aclSelect = array(
    ACL_LEVEL_PRIVATE => __('Me'),
    ACL_LEVEL_GROUP => __('Group'),
    ACL_LEVEL_USER => __('Users'),
    ACL_LEVEL_OTHER => __('All'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can view the image?")).
    $this->Html->tag('div', $this->Form->radio('acl.read.preview', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can download the image?")).
    $this->Html->tag('div', $this->Form->radio('acl.read.original', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can add tags?")).
    $this->Html->tag('div', $this->Form->radio('acl.write.tag', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can edit all meta data?")).
    $this->Html->tag('div', $this->Form->radio('acl.write.meta', $aclSelect, array('legend' => false)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));

  echo $this->Form->input('acl.group', array('type' => 'select', 'options' => $groups, 'label' => __("Default image group?")));
?>
</fieldset>

<fieldset><legend><?php echo __('GPS Tracks'); ?></legend>
<p><?php echo __('Usually GPS logs are in UTC while camera time is set to local time. Set time offset to correct time difference between GPS logs and your media.'); ?></p>
<?php
  echo $this->Form->input('filter.gps.offset', array('type' => 'text', 'label' => __("Time offset (minutes)")));
  echo $this->Form->input('filter.gps.range', array('type' => 'text',  'label' => __("Coordinate time range (minutes)")));
  echo $this->Form->input('filter.gps.overwrite', array('type' => 'checkbox',  'label' => __("Overwrite existing coordinates?")));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
