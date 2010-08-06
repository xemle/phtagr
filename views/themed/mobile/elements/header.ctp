<h1><?php echo h($option->get('general.title', 'phTagr.')); ?><span class="subheader"><?php __('mobile'); ?></span></h1>
<div class="login">
<?php 
  if ($session->check('User.id')) {
    echo $html->link(__("Logout", true), '/users/logout'); 
  } else {
    echo $html->link(__("Login", true), '/users/login'); 
  }
?>
</div>
