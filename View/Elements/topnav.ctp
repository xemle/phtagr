<?php
  if ($this->Session->check('User.id')) {
    $userId = $this->Session->read('User.id');
    $role = $this->Session->read('User.role');
    $name = $this->Session->read('User.username');
    if ($role >= ROLE_SYSOP) {
      echo $this->Html->link(__('System'), "/admin/system/general")." | ";
    }
    if ($role >= ROLE_USER) {
      echo $this->Html->link(__('Preferences'), "/options/profile")." | ";
    }
    echo $this->Html->link(__("Logout"), "/users/logout"). " ($name)";
  } else {
    echo $this->Html->link(__("Login"), "/users/login");
    if ($option->get('user.register.enable', 0)) {
      echo ' | '.$this->Html->link(__("Register"), "/users/register");
    }
  }

  echo "\n<div class=\"searchBox\" ><div class=\"searchBoxSub\" >";
  echo $this->Form->create(null, array('url' => '/explorer/quicksearch'));
  echo $this->Form->input('Media.quicksearch', array ('label' => false, 'div' => false));
  $icon = Router::url("/img/icons/zoom.png");
  echo "<input type=\"image\" src=\"$icon\" width=\"16\" height=\"16\" id=\"go\" alt=\"Search\" title=\"Search\" />";
  echo $this->Form->end();
  echo "</div></div>\n";
?>
