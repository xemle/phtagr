<?php

/** Callback frunction for rss items */
function getItem($data) {
  return array('title' => $data['Medium']['name'],
    'link' => '/images/view/'.$data['Medium']['id'].'/'.$data['Medium']['file'],
    'guid' => $data['Medium']['id'],
    'description' => $data['Medium']['caption'],
    'pubDate' => $data['Medium']['created']);
}

echo $rss->items($data, 'getItem');
?>
