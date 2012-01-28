<h1><?php __("Delete Unused Metadata"); ?></h1>
<?php echo $session->flash(); ?>
<p><?php printf(__("Delete all unused meta data from database: %d tags, %d categories, %d locations.", true), $this->data['unusedTagCount'], $this->data['unusedCategoryCount'], $this->data['unusedLocationCount']); ?>
</p><?php echo $this->Html->link(__('Delete', true), 'deleteUnusedMetaData/delete', array('class' => 'button')); ?></p>
