<h1><?php __('Authenticated RSS') ?></h1>

<div class="info">
<?php __('Following links contain authentication keys of your account. Using these links you are authenticated directly without username and password. The links could be spoofed easily and should be used carefully.'); ?>
</div>

<p><?php __('The links are valid for the current session. For permanent login the user has to login via username and password.'); ?></p>

<ul>
  <li><?php echo $html->link(__('Recent Images', true), Router::url('/explorer/rss/key:'.$data['User']['key'], true)); ?></li>
  <li><?php echo $html->link(__('Recent Comments', true), Router::url('/comments/rss/key:'.$data['User']['key'], true)); ?></li>
  <li><?php echo $html->link(__('Media RSS', true), Router::url('/explorer/media/key:'.$data['User']['key'], true)); ?></li>
</ul>

<p><?php printf(__("Click %s to renew the authentication key. All previous keys become invalid.", true), $html->link(__('renew key', true), 'rss/renew')); ?></p>
