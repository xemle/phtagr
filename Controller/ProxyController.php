<?php

App::uses('HttpSocket', 'Network/Http');

class ProxyController extends AppController
{
  public function index() {
    $this->requireRole(ROLE_USER);

    $this->autoRender = false;
    $newURL = urldecode(substr($_SERVER['REQUEST_URI'], strlen($this->request->here) + 1));
    $sock = new HttpSocket();
    if ($this->request->isPost()) {
      $results = $sock->post($newURL, $this->request->input());
    } elseif ($this->request->isGet()) {
      $results = $sock->get($newURL);
    }
    $this->response->statusCode($results->code);
    foreach ($results->headers as $h => $v) {
      $this->header("$h: $v");
    }
    if ($results->getHeader('Content-Type') != '') {
      $this->RequestHandler->respondAs($results->getHeader('Content-Type'));
    }
    echo $results->body;
  }
}
