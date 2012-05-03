<markers>
<?php foreach ($this->request->data as $media): ?>
  <marker id="<?php echo $media['Media']['id']; ?>" lat="<?php echo $media['Media']['latitude']; ?>" lng="<?php echo $media['Media']['longitude']; ?>" >
    <name><?php echo h($media['Media']['name']); ?></name>
    <icon><?php echo Router::url('/media/mini/'.$media['Media']['id'].'/mini.jpg', true); ?></icon>
    <description><![CDATA[<h3><?php
      echo $media['Media']['name'];
    ?></h3>
    <a href="<?php echo Router::url('/images/view/'.$media['Media']['id']); ?>"><img src="<?php echo Router::url('/media/mini/'.$media['Media']['id'].'/mini.jpg', true); ?>" width="75" height="75" /> view</a>]]></description>
  </marker>
<?php endforeach; /* data loop */ ?>
</markers>
