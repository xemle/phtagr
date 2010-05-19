<h1><?php __('Authenticated Links'); ?></h1>

<div class="info">
<?php __('Following links contains an authentication key which should be used carefully. Copy the links with with right mouse click and select <i>Copy link address</i>.'); ?></i>
</div>

<p><?php __('The links are valid for the current session. For permanent login the user has to login via username and password.'); ?></p>

<h2><?php __('Direct Link'); ?></h2>

<p><?php echo __('Following link provides a direct link to <i>My Photos</i> of the guest which omits the login with username and password.'); ?></p>

<?php
  $myMedia = Router::url('/explorer/user/'.$this->data['Guest']['username'].'/key:'.$this->data['Guest']['key'], true);
?>
<ul>
  <li><?php printf(__('My Media of Guest %s (Link %s)', true), $html->link($this->data['Guest']['username'], $myMedia), '<code>' . $myMedia . '</code>'); ?></li>
</ul>


<h2><?php __('RSS'); ?></h2>

<p><?php __('Following links provide a authenticated RSS and Media RSS links.'); ?></p>
<?php
  $recentMedia = Router::url('/explorer/rss/key:'.$this->data['Guest']['key'], true);
  $recentComments = Router::url('/comments/rss/key:'.$this->data['Guest']['key'], true);
  $mediaRss = Router::url('/explorer/media/key:'.$this->data['Guest']['key'].'/media.rss', true);
  $myMediaMediaRss = Router::url('/explorer/media/user:'.$this->data['Guest']['username'].'/key:'.$this->data['Guest']['key'].'/media.rss', true);
?>
<ul>
  <li><?php echo $html->link(__('Recent Media', true), $recentMedia); ?> (Link: <code><?php echo $recentMedia; ?></code>)</li>
  <li><?php echo $html->link(__('Recent Comments', true), $recentComments); ?> (Link: <code><?php echo $recentComments; ?></code>)</li>
  <li><?php echo $html->link(__('Media RSS', true), $mediaRss); ?> (Link: <code><?php echo $mediaRss; ?></code>)</li>
  <li><?php echo $html->link(__('Media RSS of My Media', true), $myMediaMediaRss); ?> (Link: <code><?php echo $myMediaMediaRss; ?></code>)</li>
</ul>

<p><?php printf(__('Click %s to renew the authentication key. All previous links become invalid.', true), $html->link(__('renew key', true), 'rss/'.$this->data['Guest']['id'].'/renew')); ?></p>
