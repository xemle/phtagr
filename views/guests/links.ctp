<h1>Authenticated Links</h1>

<div class="info">
Following links contains an authentication key which should be used carefully.
Copy the links with with right mouse click and select <i>Copy link address.</i>
</div>

<p>The links are valid for the current session. For permanent login the user has to login via username and password.</p>

<h2>Direct Link</h2>

<p>Following link provides a direct link to <i>My Images</i> of the guest which
omits the login with username and password.</p>


<ul>
  <li><?php echo $html->link('My Images of Guest '.$this->data['Guest']['username'], Router::url('/explorer/user/'.$this->data['Guest']['id'].'/key:'.$this->data['Guest']['key'], true)); ?></li>
</ul>


<h2>RSS</h2>

<p>Following links provide a authenticated RSS and Media RSS links.</p>

<ul>
  <li><?php echo $html->link('Recent Images', Router::url('/explorer/rss/key:'.$this->data['Guest']['key'], true)); ?></li>
  <li><?php echo $html->link('Recent Comments', Router::url('/comments/rss/key:'.$this->data['Guest']['key'], true)); ?></li>
  <li><?php echo $html->link('Media RSS', Router::url('/explorer/media/key:'.$this->data['Guest']['key'].'/media.rss', true)); ?></li>
  <li><?php echo $html->link('My Images Media RSS', Router::url('/explorer/media/user:'.$this->data['Guest']['id'].'/key:'.$this->data['Guest']['key'].'/media.rss', true)); ?></li>
</ul>

<p>Click <?php echo $html->link('renew key',
'rss/'.$this->data['Guest']['id'].'/renew'); ?> to renew the authentication
key. All previous links become invalid.</p>

