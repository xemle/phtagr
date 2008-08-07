<?php

/** Callback frunction for rss items */
function getItem($data) {
  return array('title' => $data['Comment']['name'].' comment on '.$data['Image']['name'],
    'link' => '/images/view/'.$data['Image']['id'].'/'.$data['Image']['file'],
    'guid' => Router::url($data['Image']['id'].'-'.$data['Comment']['id'], true),
    'description' => $data['Comment']['text'],
    'pubDate' => $data['Comment']['created']);
}

echo $rss->items($data, 'getItem');
?>
