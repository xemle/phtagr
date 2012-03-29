<?php
$this->set('channelData', array(
    'title' => __("Most Recent Media"),
    'link' => $this->Html->url('/explorer', true),
    'description' => __("Most recent media of %s", $this->Html->url('/', true)),
    'language' => 'en-us'));

foreach ($data as $media) {
  echo $this->Rss->item(array(), array(
        'title' => $media['Media']['name'],
        'link' => '/images/view/' . $media['Media']['id'],
        'guid' => array('url' => $this->Html->url('/images/view/' . $media['Media']['id'], true), 'isPermaLink' => 'true'),
        'description' => $media['Media']['caption'],
        'pubDate' => $media['Media']['created']
    ));
}
