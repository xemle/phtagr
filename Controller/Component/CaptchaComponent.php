<?php
App::import('Vendor', 'kcaptcha/kcaptcha');

class CaptchaComponent extends Component
{
  public function initialize(Controller $controller) {
    $this->controller = $controller;
  }

  public function render($name = 'captcha') {
    $kcaptcha = new KCAPTCHA();
    $this->controller->Session->write($name, $kcaptcha->getKeyString());
  }
}
?>