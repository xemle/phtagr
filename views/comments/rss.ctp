<?php

/** Callback frunction for rss items */
function getItem($data) {
  return array('title' => $data['Comment']['name'].' comment on '.$data['Media']['name'],
    'link' => '/images/view/'.$data['Media']['id'].'/'.$data['Media']['file'],
    'guid' => Router::url($data['Media']['id'].'-'.$data['Comment']['id'], true),
    'description' => $data['Comment']['text'],
    'pubDate' => $data['Comment']['created']);
}

echo $rss->items($data, 'getItem');
?>
