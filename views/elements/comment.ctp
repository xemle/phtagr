<?php 
  $search->initialize();
  $searchParams = $search->serialize(false, false, false, array('defaults' => array('pos' => 1)));
?>
<div id="comments">
<?php if (count($this->data['Comment'])): ?>
<h3><?php echo __('Comments', true); ?></h3>
<?php $count = 0; ?>
<?php foreach ($this->data['Comment'] as $key => $comment): ?>
<?php if (!is_numeric($key)) continue; ?>
<div class="comment <?php echo ($count++%2)?'even':'odd'; ?>">
<div class="meta">
<span class="from"><?php 
  $name = $comment['name'];
  if (!empty($comment['url'])) {
    $name = $html->link($comment['name'], $comment['url']);
  }  
  $time = $html->tag('span', $this->Time->relativeTime($comment['date']), array('class' => 'date'));
  printf(__("%s said %s", true), $name, $time);
?>
<?php
  if ($this->data['Media']['isOwner'] || $comment['user_id'] == $userId) {
    echo $html->link(__('(delete)', true), '/comments/delete/'.$comment['id'].'/'.$searchParams);
  }
?>:
</div><!-- comment meta -->

<div class="text">
<?php echo preg_replace('/\n/', '<br/>', $comment['text']); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
<?php endif; /* has comments */ ?>

<h3><?php __("Add new Comment", true); ?></h3>
<?php echo $form->create('Comment', array('action' => 'add/'.$searchParams, 'id' => 'comment-add', 'class' => 'default')); ?>
<fieldset>
<?php
  echo $form->hidden('Media.id', array('value' => $this->data['Media']['id']));
?>
<?php 
  if (($commentAuth & COMMENT_AUTH_NAME) > 0) {
    echo $form->input('Comment.name', array('label' => __('Name', true)));
    echo $form->input('Comment.email', array('after' => '<span class="description">' . __('Will not be published', true) . '</span>'));
    echo $form->input('Comment.url', array('after' => '<span class="description">' . __('Optional', true) . '</span>', 'required' => false));
  }
  if (($commentAuth & COMMENT_AUTH_CAPTCHA) > 0) {
    echo '<div class="input text"><label>&nbsp;</label><img src="'.$html->url('/comments/captcha/verify.jpg').'" /></div>';
    echo $form->input('Captcha.verification', array('label' => __('Verification', true)));
  }
?>
<?php
  echo $form->input('Comment.text', array('label' => __('Comment', true)));
  echo $form->input('Comment.notify', array('type' => 'checkbox', 'label' => __('Notify me on new comments', true), 'checked' => 'checked'));
?>
</fieldset>
<?php echo $form->end(__('Add Comment', true)); ?>
</div><!-- comments -->
