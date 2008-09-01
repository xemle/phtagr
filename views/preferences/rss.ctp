<h1>Authenticated RSS</h1>

<div class="info">
Following links contain authentication keys of your account. Using these links
you are authenticated directly without username and password. The links could
be spoofed easily and should be used carefully.
</div>

<p>The links are valid for the current session. For permanent login the user has to login via username and password.</p>

<ul>
  <li><?php echo $html->link('Recent Images', Router::url('/explorer/rss/key:'.$data['User']['key'], true)); ?></li>
  <li><?php echo $html->link('Recent Comments', Router::url('/comments/rss/key:'.$data['User']['key'], true)); ?></li>
  <li><?php echo $html->link('Media RSS', Router::url('/explorer/media/key:'.$data['User']['key'], true)); ?></li>
</ul>

<p>Click <?php echo $html->link('renew key', 'rss/renew'); ?> to renew the authentication key. All previous keys become invalid.</p>
