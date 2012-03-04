<?php

/** Callback frunction for rss items */
function getItem($data) {
  return array('title' => $data['Media']['name'],
    'link' => '/images/view/'.$data['Media']['id'],
    'guid' => $data['Media']['id'],
    'description' => $data['Media']['caption'],
    'pubDate' => $data['Media']['created']);
}

echo $rss->items($this->request->data, 'getItem');
?>
