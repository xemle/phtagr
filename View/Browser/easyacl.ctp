<h1><?php echo __('Bulk Access Right Change')?></h1>

<?php echo $this->Session->flash() ?>

<?php echo $this->Form->create(null, array('action' => "easyacl")); ?>

<fieldset><legend><?php echo __('Media Selection Criteria'); ?></legend>

<?php
  echo $this->Form->input('Field.keyword', array('label' => __('Media with keyword:'), 'value' => join(', ', Set::extract('/Field[name=keyword]/data', $this->request->data))));
  echo $this->Autocomplete->autoComplete('Field.keyword', '/explorer/autocomplete/tag', array('split' => true));

  echo $this->Form->input('Group.names', array('value' => '', 'label' => __("Media in group")));
  echo $this->Autocomplete->autoComplete('Group.names', '/explorer/autocomplete/aclgroup', array('split' => true));
?>
</fieldset>

<fieldset><legend><?php echo __('Edit access rights (ACL) for matching media'); ?></legend>
<?php
  $aclSelect = array(
    ACL_LEVEL_PRIVATE => __('Me'),
    ACL_LEVEL_GROUP => __('Group'),
    ACL_LEVEL_USER => __('Users'),
    ACL_LEVEL_OTHER => __('All'),
    ACL_LEVEL_KEEP => __('Keep'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can view the image")).
    $this->Html->tag('div', $this->Form->radio('Media.readPreview', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can download the image?")).
    $this->Html->tag('div', $this->Form->radio('Media.readOriginal', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can add tags?")).
    $this->Html->tag('div', $this->Form->radio('Media.writeTag', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can edit all meta data?")).
    $this->Html->tag('div', $this->Form->radio('Media.writeMeta', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));

?>
</fieldset>
<?php echo $this->Form->end(__('Update')); ?>
