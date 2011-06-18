<?php echo $this->element('explorer/date', array('media' => $media)); ?>
<?php if (count($media['Tag'])): ?>
  <dd class="tag list"><?php echo __("Tags", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))); ?></dt>
<?php endif; ?>
<?php if (count($media['Category'])): ?>
  <dd class="category list"><?php echo __("Categories", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/category', Set::extract('/Category/name', $media))); ?></dt>
<?php endif; ?>
<?php if (count($media['Location'])): ?>
  <dd class="location list"><?php echo __("Locations", true); ?></dd>
  <dt><?php echo implode(', ', $imageData->linkList('/explorer/location', Set::extract('/Location/name', $media))); ?></dt>
<?php endif; ?>
<?php if ($search->getUser() == $currentUser['User']['username'] && !empty($media['Group']['name'])): ?>
  <dd class="group list"><?php echo __("Group", true); ?></dd>
  <dt><?php echo $html->link($media['Group']['name'], '/explorer/group/' . $media['Group']['name']); ?></dt>
<?php endif; ?>
<?php if ($search->getUser() == $currentUser['User']['username']): ?>
  <dd class="access list"><?php echo __("Access", true); ?></dd>
  <dt><?php 
    echo $imageData->_acl2icon($media['Media']['gacl'], __('Group members', true)).', ';
    echo $imageData->_acl2icon($media['Media']['uacl'], __('Users', true)).', ';
    echo $imageData->_acl2icon($media['Media']['oacl'], __('All', true));
  ?></dt>
<?php endif; ?>
</dl>
