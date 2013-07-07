<?php echo $this->Session->flash(); ?>

<?php echo $this->Form->create('User', array('url' => 'confirm')); ?>
<fieldset>
<legend><?php echo __('Account Confirmation'); ?></legend>
<p><?php echo __('Please insert your confirmation key to finalize the account creation.'); ?></p>
<?php
  echo $this->Form->input('User.key', array('label' => __('Key')));
?>
</fieldset>
<?php echo $this->Form->end(__('Confirm')); ?>
<?php
  $script = <<<SCRIPT
(function($) {
  $(document).ready(function() {
    $(':submit').button();
    $('.button').button();
  });
})(jQuery);
SCRIPT;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>

