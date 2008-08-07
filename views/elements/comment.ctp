<?php 
  $query->initialize();
  $params = $query->getParams();
?>
<div class="comments">
<?php if (count($data['Comment'])): ?>
<h3>Comments</h3>
<?php $count = 0; ?>
<?php foreach ($data['Comment'] as $comment): ?>
<div class="comment <?php echo ($count++%2)?'even':'odd'; ?>">
<div class="meta">
<span class="from"><?php echo $comment['name'] ?></span> said 
<span class="date"><?php echo $time->relativeTime($comment['date']); ?></span>
<?php 
  if ($data['Image']['isOwner'] || $comment['user_id'] == $userId) {
    echo $html->link('(delete)', '/comments/delete/'.$comment['id'].'/'.$params);
  }
?>:
</div><!-- comment meta -->

<div class="text">
<?php echo preg_replace('/\n/', '<br/>', $comment['text']); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
<?php endif; /* has comments */ ?>

<h3>Add new Comment</h3>
<?php echo $form->create('Comment', array('action' => 'add/'.$params)); ?>
<?php
  echo $form->hidden('Image.id', array('value' => $data['Image']['id']));
?>
<?php 
  if ($userRole == ROLE_NOBODY) {
    echo $form->input('Comment.name');
    echo $form->input('Comment.email');
    echo '<div class="input text"><label>&nbsp;</label><img src="'.$html->url('/comments/captcha').'" /></div>';
    echo $form->input('Captcha.verification');
  }
?>
<?php
  echo $form->input('Comment.text', array('label' => 'Comment'));
?>
<?php echo $form->submit('Add Comment'); ?>
<?php echo $form->end(); ?>
</div><!-- comments -->
