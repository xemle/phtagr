<?php
  $this->Search->initialize();
  $this->SearchParams = $this->Search->serialize(false, false, false, array('defaults' => array('pos' => 1)));
?>
<div id="comments">
<?php if (count($this->request->data['Comment'])): ?>
<h3><?php echo __('Comments'); ?></h3>
<?php $count = 0; ?>
<?php foreach ($this->request->data['Comment'] as $key => $comment): ?>
<?php if (!is_numeric($key)) continue; ?>
<div class="comment <?php echo ($count++%2)?'even':'odd'; ?>">
<div class="meta">
<span class="from"><?php
  $name = $comment['name'];
  if (!empty($comment['url'])) {
    $name = $this->Html->link($comment['name'], $comment['url']);
  }
  $time = $this->Html->tag('span', $this->Time->timeAgoInWords($comment['date']), array('class' => 'date'));
  echo __("%s said %s", $name, $time);
?>
<?php
  if ($this->request->data['Media']['isOwner'] || $comment['user_id'] == $userId) {
    echo $this->Html->link(__('(delete)'), '/comments/delete/'.$comment['id'].'/'.$this->SearchParams);
  }
?>:
</div><!-- comment meta -->

<div class="text">
<?php echo preg_replace('/\n/', '<br/>', $comment['text']); ?>
</div>
</div><!-- comment -->
<?php endforeach; /* comments */ ?>
<?php endif; /* has comments */ ?>

<h3><?php echo __("Add new Comment"); ?></h3>
<?php echo $this->Form->create('Comment', array('action' => 'add/'.$this->SearchParams, 'id' => 'comment-add')); ?>
<fieldset>
<?php
  echo $this->Form->hidden('Media.id', array('value' => $this->request->data['Media']['id']));
?>
<?php
  if (($commentAuth & COMMENT_AUTH_NAME) > 0) {
    echo $this->Form->input('Comment.name', array('label' => __('Name')));
    echo $this->Form->input('Comment.email', array('after' => '<span class="description">' . __('Will not be published') . '</span>'));
    echo $this->Form->input('Comment.url', array('after' => '<span class="description">' . __('Optional') . '</span>', 'required' => false));
  }
  if (($commentAuth & COMMENT_AUTH_CAPTCHA) > 0) {
    echo '<div class="input text"><label>&nbsp;</label><img src="'.$this->Html->url('/comments/captcha/verify.jpg').'" /></div>';
    echo $this->Form->input('Captcha.verification', array('label' => __('Verification')));
  }
?>
<?php
  echo $this->Form->input('Comment.text', array('label' => __('Comment')));
  echo $this->Form->input('Comment.notify', array('type' => 'checkbox', 'label' => __('Notify me on new comments'), 'checked' => 'checked'));
?>
</fieldset>
<?php echo $this->Form->end(__('Add Comment')); ?>
</div><!-- comments -->
