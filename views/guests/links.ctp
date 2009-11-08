<h1>Authenticated Links</h1>

<div class="info">
Following links contains an authentication key which should be used carefully.
Copy the links with with right mouse click and select <i>Copy link address.</i>
</div>

<p>The links are valid for the current session. For permanent login the user has to login via username and password.</p>

<h2>Direct Link</h2>

<p>Following link provides a direct link to <i>My Images</i> of the guest which
omits the login with username and password.</p>

<?php
  $myMedia = Router::url('/explorer/user/'.$this->data['Guest']['username'].'/key:'.$this->data['Guest']['key'], true);
?>
<ul>
  <li><?php echo $html->link('My Media of Guest '.$this->data['Guest']['username'], $myMedia); ?> (Link: <code><?php echo $myMedia; ?></code>)</li>
</ul>


<h2>RSS</h2>

<p>Following links provide a authenticated RSS and Media RSS links.</p>
<?php
  $recentMedia = Router::url('/explorer/rss/key:'.$this->data['Guest']['key'], true);
  $recentComments = Router::url('/comments/rss/key:'.$this->data['Guest']['key'], true);
  $mediaRss = Router::url('/explorer/media/key:'.$this->data['Guest']['key'].'/media.rss', true);
  $myMediaMediaRss = Router::url('/explorer/media/user:'.$this->data['Guest']['username'].'/key:'.$this->data['Guest']['key'].'/media.rss', true);
?>
<ul>
  <li><?php echo $html->link('Recent Media', $recentMedia); ?> (Link: <code><?php echo $recentMedia; ?></code>)</li>
  <li><?php echo $html->link('Recent Comments', $recentComments); ?> (Link: <code><?php echo $recentComments; ?></code>)</li>
  <li><?php echo $html->link('Media RSS', $mediaRss); ?> (Link: <code><?php echo $mediaRss; ?></code>)</li>
  <li><?php echo $html->link('Media RSS of My Media', $myMediaMediaRss); ?> (Link: <code><?php echo $myMediaMediaRss; ?></code>)</li>
</ul>

<p>Click <?php echo $html->link('renew key',
'rss/'.$this->data['Guest']['id'].'/renew'); ?> to renew the authentication
key. All previous links become invalid.</p>

