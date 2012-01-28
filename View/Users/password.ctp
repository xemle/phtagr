<h1><?php __('Password Request'); ?></h1>
<?php echo $session->flash(); ?>

<p><?php __("Please insert your username and email address to request your lost password."); ?>

<?php echo $form->create('User', array('action' => 'password')); ?>
<fieldset><legend><?php __('Account Data'); ?></legend>
<?php
  echo $form->input('User.username', array('label' => __('Username', true)));
  echo $form->input('User.email', array('label' => __('Email', true)));
?>
</fieldset>
<?php echo $form->end(__('Submit', true)); ?>
<?php 
  $script = <<<'JS'
(function($) {
  $(document).ready(function() {
    $(':submit').button();
  }); 
})(jQuery);
JS;
  echo $this->Html->scriptBlock($script, array('inline' => false));
?>
