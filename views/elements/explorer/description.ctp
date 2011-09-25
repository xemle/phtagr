<?php $search->initialize(); ?>
<?php echo $this->element('explorer/date', array('media' => $media)); ?>
<?php if (count($media['Tag'])): ?>
  <p class="tag list"><?php echo __("Tags", true); ?></dd>
  <?php echo implode(', ', $imageData->linkList('/explorer/tag', Set::extract('/Tag/name', $media))); ?></p>
<?php endif; ?>
<?php if (count($media['Category'])): ?>
  <p class="category list"><?php echo __("Categories", true); ?>
  <?php echo implode(', ', $imageData->linkList('/explorer/category', Set::extract('/Category/name', $media))); ?></p>
<?php endif; ?>
<?php if (count($media['Location'])): ?>
  <p class="location list"><?php echo __("Locations", true); ?>
  <?php echo implode(', ', $imageData->linkList('/explorer/location', Set::extract('/Location/name', $media))); ?></p>
<?php endif; ?>
<?php if ($search->getUser() == $currentUser['User']['username'] && !empty($media['Group']['name'])): ?>
  <p class="group list"><?php echo __("Group", true); ?></dd>
  <?php echo $html->link($media['Group']['name'], '/explorer/group/' . $media['Group']['name']); ?></p>
<?php endif; ?>
<?php if ($search->getUser() == $currentUser['User']['username']): ?>
  <p class="access list"><?php echo __("Access", true); ?>
  <?php 
    echo $imageData->_acl2icon($media['Media']['gacl'], __('Group members', true)).', ';
    echo $imageData->_acl2icon($media['Media']['uacl'], __('Users', true)).', ';
    echo $imageData->_acl2icon($media['Media']['oacl'], __('All', true));
  ?></p>
<?php endif; ?>
