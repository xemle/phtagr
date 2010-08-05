<h1><?php __('Explorer'); ?></h1>
<?php echo $session->flash(); ?>

<?php 
  $search->initialize();
?>
<?php if ($navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $navigator->prev().' '.$navigator->numbers().' '.$navigator->next(); ?>
</div></div>
<?php endif; ?>

<div class="thumbs">
<script type="text/javascript">
  var mediaData = [];
</script>
<?php
$cell=0;
$canWriteTag=false;
$canWriteMeta=false;
$canWriteAcl=false;
$pos = ($search->getPage(1)-1) * $search->getShow(12) + 1;
foreach ($this->data as $media): ?>
<?php $side = $cell % 2 ? 'r' : 'l'; ?>
<?php if (!($cell % 2)): ?><div class="subcolumns"><?php endif; ?>
<?php 
  $icon = $imageData->getVisibilityIcon(&$media);
?>
<div class="c50<?=$side; ?>"><div class="subc<?=$side; ?> unselected thumb" id="media-<?= $media['Media']['id'];?>" >
<script type="text/javascript">
  mediaData[<?php echo $media['Media']['id']; ?>] = [];
</script>
<h2><?php if ($icon) { echo $icon.' '; } ?><?php echo $media['Media']['name']; ?></h2>
<div class="image">
<?php 
  $size = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  echo "<a href=\"".Router::url("/images/view/".$media['Media']['id'].'/'.$search->serialize(false, array('pos' => $pos++), false, array('defaults' => array('pos' => 1))))."\">";
  echo "<img src=\"".Router::url("/media/thumb/".$media['Media']['id'])."\" $size[3] alt=\"".$media['Media']['name']."\"/>"; 
  echo "</a>";

  if ($media['Media']['canWriteTag']) {
    $canWriteTag=true;
  }
  if ($media['Media']['canWriteMeta']) {
    $canWriteMeta=true;
  }
  if ($media['Media']['canWriteAcl']) {
    $canWriteAcl=true;
  }
?>
</div>

<div class="user">
<?php
  if (!$search->getUser() || $search->getUser() != $session->read('User.username')) {
    echo "by ".$html->link($media['User']['username'], "/explorer/user/".$media['User']['username']);
  }
  $extra = array();
  if ($media['Media']['clicks'] > 0) {
    $extra[] = $media['Media']['clicks'].' '.$html->image('icons/eye.png', array('alt' => 'clicks', 'title' => "{$media['Media']['clicks']} clicks"));;
  }
  if (count($media['Comment'])) {
    $extra[] = count($media['Comment']).' '.$html->image('icons/comments.png', array('alt' => 'comments', 'title' => count($media['Comment'])." comments"));
  }
  if ($extra) {
    echo " (".implode(', ', $extra).")";
  }
?>
</div>

<div class="meta">
<div id="<?php echo 'meta-'.$media['Media']['id']; ?>">
<table>
  <?php echo $html->tableCells($imageData->metaTable(&$media)); ?>
</table>
</div>
</div><!-- meta -->

</div><!-- c50 --></div><!-- subc -->
<?php if ($side == 'r'): ?></div><!-- subcolumns --><?php endif; ?>
<?php $cell++; endforeach; ?>
<?php /* fix for odd number */ if ($cell % 2): ?></div><!-- subcolumns --><?php endif; ?>
</div>

<?php if ($canWriteTag): ?>
<div class="paginator"><div class="subpaginator">
<a href="javascript:void(0);" onclick="thumbSelectAll();"><?php __('Select All'); ?></a>
<a href="javascript:void(0);" onclick="thumbSelectInvert();"><?php __('Invert Selection'); ?></a>
</div></div>
<?php endif; ?>

<?php if ($navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $navigator->prev().' '.$navigator->numbers().' '.$navigator->next(); ?>
</div></div>
<?php endif; ?>

<div class="edit">

<?php if ($canWriteTag): ?>
<?php 
  $items = array(array('name' => __("Metadata", true), 'active' => true));
  if ($canWriteAcl) {
    $items[] = __("Access Rights", true);
  }
  echo $tab->menu($items);

  $url = $search->serialize();
  echo $form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$url));
?>
<?php echo $tab->open(0, true); ?>
<fieldset>
<?php echo $form->hidden('Media.ids', array('id' => 'MediaIds')) ?>
<?php 
  if ($canWriteMeta) {
    echo $form->input('Media.date', array('type' => 'text', 'after' => '<span class="hint">' . __('E.g. 2008-08-07 15:30', true) . '</span>')); 
  }
  echo $html->tag('div',
    $form->label('Tags.text', __('Tags', true)).
    $ajax->autoComplete('Tags.text', 'autocomplete/tag', array('tokens' => ',')) . 
    $html->tag('span', __('E.g. newtag, -oldtag', true), array('class' => 'hint')),
    array('class' => 'input text'));

  if ($canWriteMeta) {
    echo $html->tag('div',
      $form->label('Categories.text', __('Categories', true)).
      $ajax->autoComplete('Categories.text', 'autocomplete/category', array('tokens' => ',')), 
      array('class' => 'input text'));
    echo $html->tag('div',
      $form->label('Locations.city', __('City', true)).
      $ajax->autoComplete('Locations.city', 'autocomplete/city'), 
      array('class' => 'input text'));
    echo $html->tag('div',
      $form->label('Locations.sublocation', __('Sublocation', true)).
      $ajax->autoComplete('Locations.sublocation', 'autocomplete/sublocation'), 
      array('class' => 'input text'));
    echo $html->tag('div',
      $form->label('Locations.state', __('State', true)).
      $ajax->autoComplete('Locations.state', 'autocomplete/state'), 
      array('class' => 'input text'));
    echo $html->tag('div',
      $form->label('Locations.country', __('Country', true)).
      $ajax->autoComplete('Locations.country', 'autocomplete/country'), 
      array('class' => 'input text'));
    echo $form->input('Media.geo', array('label' => __('Geo data', true), 'maxlength' => 32, 'after' => '<span class="hint">' . __('latitude, longitude', true) . '</span>'));
  }
?>
</fieldset>
<?php echo $tab->close(); ?>
<?php if ($canWriteAcl): ?>
<?php echo $tab->open(1); ?>
<fieldset>
<?php
  $aclSelect = array(
    ACL_LEVEL_KEEP => __('[Keep]', true),
    ACL_LEVEL_OTHER => __('Everyone', true),
    ACL_LEVEL_USER => __('Users', true),
    ACL_LEVEL_GROUP => __('Group members', true),
    ACL_LEVEL_PRIVATE => __('Me only', true));
  echo $form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => __("Who can view the image?", true)));
  echo $form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => __("Who can download the image?", true)));
  echo $form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => __("Who can add tags?", true)));
  echo $form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => __("Who can edit all meta data?", true)));
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => 0, 'label' => __("Default image group?", true)));
?>
</fieldset>
<?php echo $tab->close(); ?>
<?php endif; // canWriteAcl==true ?>
<?php echo $form->end(__('Apply', true)); ?>
<?php endif; // canWriteTag==true ?>
</div>
