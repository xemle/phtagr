<h1><?php __('Guest Creation'); ?></h1>

<?php echo $session->flash(); ?>

<?php echo $form->create('Guest', array('action' => 'add')); ?>

<fieldset><legend><?php __('Create new guest account'); ?></legend>
<?php
  echo $form->input('Guest.username', array('label' => __('Username', true)));
  echo $form->input('Guest.email', array('label' => __('Email', true)));
  echo $form->input('Guest.password', array('label' => __('Password', true)));
  echo $form->input('Guest.confirm', array('type' => 'password', 'label' => __('Confirm', true)));
?>
</fieldset>
<?php 
  echo $html->tag('ul', 
    $html->tag('li', $form->submit(__('Add', true)), array('escape' => false)),
    array('class' => 'buttons', 'escape' => false));
  echo $form->end();
?>
