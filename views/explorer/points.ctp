<markers>
<?php foreach ($this->data as $medium): ?>
  <marker id="<?php echo $medium['Medium']['id']; ?>" lat="<?php echo $medium['Medium']['latitude']; ?>" lng="<?php echo $medium['Medium']['longitude']; ?>" >
    <name><?php echo $medium['Medium']['name']; ?></name>
    <icon><?php echo Router::url('/media/mini/'.$medium['Medium']['id'].'/'.$medium['Medium']['file'], true); ?></icon>
    <description><![CDATA[<h3><?php 
      echo $medium['Medium']['name']; 
    ?></h3>
    <a href="<?php echo Router::url('/images/view/'.$medium['Medium']['id']); ?>"><img src="<?php echo Router::url('/media/mini/'.$medium['Medium']['id'].'/'.$medium['Medium']['file'], true); ?>" width="75" height="75" /> view</a>]]></description>
  </marker>
<?php endforeach; /* data loop */ ?>
</markers>
