<h1><?php echo __("Delete Unused Metadata"); ?></h1>
<?php echo $this->Session->flash(); ?>
<p><?php echo __("Delete all unused meta data from database: %d tags, %d categories, %d locations.", $this->data['unusedTagCount'], $this->data['unusedCategoryCount'], $this->data['unusedLocationCount']); ?>
</p><?php echo $this->Html->link(__('Delete'), 'deleteUnusedMetaData/delete', array('class' => 'button')); ?></p>
