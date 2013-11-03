<h1><?php echo h($this->Option->get('general.title', 'phTagr.')); ?><span class="subheader"><?php echo __('mobile'); ?></span></h1>
<div class="login">
<?php
  if ($this->Session->read('userId') > 0) {
    echo $this->Html->link(__("Logout"), '/users/logout');
  } else {
    echo $this->Html->link(__("Login"), '/users/login');
  }
?>
</div>
