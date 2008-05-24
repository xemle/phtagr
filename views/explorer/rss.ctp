<?php
function getItem($data) {
  return array('title' => $data['Image']['name'],
    'link' => "/explorer/image/" . $data['Image']['id'],
    'guid' => "/explorer/image/" . $data['Image']['id'],
    'description' => $data['Image']['caption'],
    'pubDate' => $data['Image']['created']);
}

echo $rss->items($data, 'getItem');
