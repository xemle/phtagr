<?php

/** Callback frunction for rss items */
function getItem($data) {
  return array('title' => $data['Image']['name'],
    'link' => '/images/view/'.$data['Image']['id'].'/'.$data['Image']['file'],
    'guid' => $data['Image']['id'],
    'description' => $data['Image']['caption'],
    'pubDate' => $data['Image']['created']);
}

echo $rss->items($data, 'getItem');
?>
