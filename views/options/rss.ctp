<h1><?php __('Authenticated RSS') ?></h1>

<div class="info">
<?php __('Following links contain authentication keys of your account. Using these links you are authenticated directly without username and password. The links could be spoofed easily and should be used carefully.'); ?>
</div>

<p><?php __('The links are valid for the current session. For permanent login the user has to login via username and password.'); ?></p>

<?php
  $recentMedia = Router::url('/explorer/rss/key:'.$this->data['User']['key'], true);
  $recentComments = Router::url('/comments/rss/key:'.$this->data['User']['key'], true);
  $mediaRss = Router::url('/explorer/media/key:'.$this->data['User']['key'].'/media.rss', true);
  $myMediaMediaRss = Router::url('/explorer/media/user:'.$this->data['User']['username'].'/key:'.$this->data['User']['key'].'/media.rss', true);
?>
<ul>
  <li><?php echo $html->link(__('Recent Media', true), $recentMedia); ?> (Link: <code><?php echo $recentMedia; ?></code>)</li>
  <li><?php echo $html->link(__('Recent Comments', true), $recentComments); ?> (Link: <code><?php echo $recentComments; ?></code>)</li>
  <li><?php echo $html->link(__('Media RSS', true), $mediaRss); ?> (Link: <code><?php echo $mediaRss; ?></code>)</li>
  <li><?php echo $html->link(__('Media RSS of My Media', true), $myMediaMediaRss); ?> (Link: <code><?php echo $myMediaMediaRss; ?></code>)</li>
</ul>

<p><?php printf(__("Click %s to renew the authentication key. All previous keys become invalid.", true), $html->link(__('renew key', true), 'rss/renew')); ?></p>
