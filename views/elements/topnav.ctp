<?php 
  if ($session->check('User.id')) {
    $userId = $session->read('User.id');
    $role = $session->read('User.role');
    $name = $session->read('User.username');
    if ($role >= ROLE_SYSOP) {
      echo $html->link('System', "/admin/users")." | ";
    }
    if ($role >= ROLE_USER) {
      echo $html->link('Preferences', "/options/profile")." | ";
    }
    echo $html->link(_("Logout"), "/users/logout"). " ($name)";
  } else {
    echo $html->link(_("Login"), "/users/login");
  }
?>
