<h1><?php echo __('Authenticated Links'); ?></h1>

<div class="info">
<?php echo __('Following links contains an authentication key which should be used carefully. Copy the links with with right mouse click and select <i>Copy link address</i>.'); ?></i>
</div>

<p><?php echo __('The links are valid for the current session. For permanent login the user has to login via username and password.'); ?></p>

<h2><?php echo __('Direct Link'); ?></h2>

<p><?php echo __('Following link provides a direct link to <i>My Photos</i> of the guest which omits the login with username and password.'); ?></p>

<?php
  $myMedia = Router::url('/explorer/user/'.$this->request->data['Guest']['username'].'/key:'.$this->request->data['Guest']['key'], true);
?>
<ul>
  <li><?php echo __('My Media of Guest %s (Link %s)', $this->Html->link($this->request->data['Guest']['username'], $myMedia), '<code>'. $myMedia . '</code>'); ?></li>
</ul>


<h2><?php echo __('RSS'); ?></h2>

<p><?php echo __('Following links provide a authenticated RSS and Media RSS links.'); ?></p>
<?php
  $recentMedia = Router::url('/explorer/rss/key:'.$this->request->data['Guest']['key'], true);
  $recentComments = Router::url('/comments/rss/key:'.$this->request->data['Guest']['key'], true);
  $mediaRss = Router::url('/explorer/media/key:'.$this->request->data['Guest']['key'].'/media.rss', true);
  $myMediaMediaRss = Router::url('/explorer/media/user:'.$this->request->data['Guest']['username'].'/key:'.$this->request->data['Guest']['key'].'/media.rss', true);
?>
<ul>
  <li><?php echo $this->Html->link(__('Recent Media'), $recentMedia); ?> (Link: <code><?php echo $recentMedia; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Recent Comments'), $recentComments); ?> (Link: <code><?php echo $recentComments; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Media RSS'), $mediaRss); ?> (Link: <code><?php echo $mediaRss; ?></code>)</li>
  <li><?php echo $this->Html->link(__('Media RSS of My Media'), $myMediaMediaRss); ?> (Link: <code><?php echo $myMediaMediaRss; ?></code>)</li>
</ul>

<p><?php echo __('Click %s to renew the authentication key. All previous links become invalid.', $this->Html->link(__('renew key'), 'links/'.$this->request->data['Guest']['id'].'/renew')); ?></p>
