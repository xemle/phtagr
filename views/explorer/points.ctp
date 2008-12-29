<markers>
<?php foreach ($this->data as $image): ?>
  <marker id="<?php echo $image['Image']['id']; ?>" lat="<?php echo $image['Image']['latitude']; ?>" lng="<?php echo $image['Image']['longitude']; ?>" >
    <name><?php echo $image['Image']['name']; ?></name>
    <icon><?php echo Router::url('/media/mini/'.$image['Image']['id'].'/'.$image['Image']['file'], true); ?></icon>
    <description><![CDATA[<h3><?php 
      echo $image['Image']['name']; 
    ?></h3>
    <a href="<?php echo Router::url('/images/view/'.$image['Image']['id']); ?>"><img src="<?php echo Router::url('/media/mini/'.$image['Image']['id'].'/'.$image['Image']['file'], true); ?>" width="75" height="75" /> view</a>]]></description>
  </marker>
<?php endforeach; /* data loop */ ?>
</markers>
