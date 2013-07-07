<h1><?php echo __('Explorer settings'); ?></h1>

<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create(null, array('url' => 'explorer')); ?>
<fieldset><legend><?php echo __('Explorer'); ?></legend>
<?php
  $shows = array(
      "12" => 12,
      "24" => 24,
      "60" => 60,
      "120" => 120,
      "240" => 240,
  );
  echo $this->Form->input('explorer.default.show', array('label' => __('Media per page'), 'options' => $shows));
  $sorts = array(
      'date' => __("Date"),
      '-date' => __("Date desc"),
      'newest' => __("Newest"),
      'name' => __("Name"),
      'changes' => __("Changes"),
  );
  echo $this->Form->input('explorer.default.sort', array('label' => __('Sort Order'), 'options' => $sorts));
  $views = array(
      'full' => __("Full"),
      'compact' => __("Compact"),
      'small' => __("Small"),
  );
  echo $this->Form->input('explorer.default.view', array('label' => __('Media view'), 'options' => $views));
?>
</fieldset>

<?php echo $this->Form->end(__('Save')); ?>
