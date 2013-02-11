<h1><?php echo __('Authenticated RSS') ?></h1>

<div class="info">
<?php echo __('Following links contain authentication keys of your account. Using these links you are authenticated directly without username and password. The links could be spoofed easily and should be used carefully.'); ?>
</div>

<p><?php echo __('The links are valid for the current session. For permanent login the user has to login via username and password.'); ?></p>

<?php
  $recentMedia = Router::url('/explorer/rss/key:'.$this->request->data['User']['key'], true);
  $recentComments = Router::url('/comments/rss/key:'.$this->request->data['User']['key'], true);
  $mediaRss = Router::url('/explorer/media/key:'.$this->request->data['User']['key'].'/media.rss', true);
  $myMediaMediaRss = Router::url('/explorer/media/user:'.$this->request->data['User']['username'].'/key:'.$this->request->data['User']['key'].'/media.rss', true);
?>
<ul>
  <li><?php echo $this->Html->link(__('Recent Media'), $recentMedia); ?> (Link: <code><?php echo $recentMedia; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Recent Comments'), $recentComments); ?> (Link: <code><?php echo $recentComments; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Media RSS'), $mediaRss); ?> (Link: <code><?php echo $mediaRss; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Media RSS of My Media'), $myMediaMediaRss); ?> (Link: <code><?php echo $myMediaMediaRss; ?></code>)</li>
</ul>

<p><?php echo __("Click %s to renew the authentication key. All previous keys become invalid.", $this->Html->link(__('renew key'), 'links/renew')); ?></p>
