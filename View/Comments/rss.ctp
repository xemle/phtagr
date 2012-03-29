<?php
$this->set('channelData', array(
    'title' => __("Most Recent Comments"),
    'link' => $this->Html->url('/comments', true),
    'description' => __("Most recent comments at %s", $this->Html->url('/', true)),
    'language' => 'en-us'));

foreach ($data as $comment) {
  echo $this->Rss->item(array(), array(
    'title' => $comment['Comment']['name'].' says on '.$comment['Media']['name'],
    'link' => '/images/view/'.$comment['Media']['id'],
    'guid' => Router::url($comment['Media']['id'].'-'.$comment['Comment']['id'], true),
    'description' => $comment['Comment']['text'],
    'pubDate' => $comment['Comment']['created']));
}
