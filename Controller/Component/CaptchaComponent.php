<?php
App::import('Vendor', 'kcaptcha/kcaptcha');

class CaptchaComponent extends Component
{
  function initialize(&$controller) {
    $this->controller = $controller;
  }

  function render($name = 'captcha') {
    $kcaptcha = new KCAPTCHA();
    $this->controller->Session->write($name, $kcaptcha->getKeyString());
  }
}
?>