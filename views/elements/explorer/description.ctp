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
</dl>
