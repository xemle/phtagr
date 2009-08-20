<h1>Explorer</h1>
<?php $session->flash(); ?>

<?php if (isset($mediaRss)): ?>
<script type="text/javascript" src="<?php echo Router::url('/piclenslite/piclens_optimized.js'); ?>" ></script>
<script type="text/javascript">
function startSlideshow() {
  PicLensLite.setLiteURLs({swf:'<?php echo Router::url("/piclenslite/PicLensLite.swf"); ?>'});
  PicLensLite.start({feedUrl:'<?php echo Router::url($mediaRss, true); ?>'});
}
</script>
<?php endif; ?>

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
  $icon = false;
  if ($media['Media']['isOwner']) {
    switch ($media['Media']['visibility']) {
      case ACL_LEVEL_OTHER: 
        $icon = $html->image('icons/world.png', array('title' => 'This media is public visible'));;
        break;
      case ACL_LEVEL_USER: 
        $icon = $html->image('icons/group.png', array('title' => 'This media is visible for users'));;
        break;
      case ACL_LEVEL_GROUP: 
        $icon = $html->image('icons/user.png', array('title' => 'This media is visible for group members'));;
        break;
      default: 
        $icon = $html->image('icons/stop.png', array('title' => 'This media is private'));;
        break;
    }
  }
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
  if ($this->action != 'user' ||
    $this->params['pass'][0] != $session->read('User.username')) {
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
<a href="javascript:void(0);" onclick="thumbSelectAll();">Select All</a>
<a href="javascript:void(0);" onclick="thumbSelectInvert();">Invert Selection</a>
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
  echo $form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$search->serialize())); 
?>
<fieldset><legend>Metadata</legend>
<?php echo $form->hidden('Media.ids', array('id' => 'MediaIds')) ?>
<?php 
  if ($canWriteMeta) {
    echo $form->input('Media.date', array('type' => 'text', 'after' => '<span class="hint">E.g. 2008-08-07 15:30</span>')); 
  }
  echo $form->input('Tags.text', array('label' => 'Tags', 'maxlength' => 320, 'after' => '<span class="hint">E.g. newtag, -oldtag</span>')); 
  if ($canWriteMeta) {
    echo $form->input('Categories.text', array('label' => 'Categories', 'maxlength' => 320)); 
    echo $form->input('Locations.city', array('maxlength' => 32));
    echo $form->input('Locations.sublocation', array('maxlength' => 32));
    echo $form->input('Locations.state', array('maxlength' => 32));
    echo $form->input('Locations.country', array('maxlength' => 32));
  }
?>
</fieldset>
<?php if ($canWriteAcl): ?>
<fieldset><legend>Access Rights</legend>
<?php
  $aclSelect = array(
    ACL_LEVEL_KEEP => '[Keep]',
    ACL_LEVEL_OTHER => 'Everyone',
    ACL_LEVEL_USER => 'Users',
    ACL_LEVEL_GROUP => 'Group members',
    ACL_LEVEL_PRIVATE => 'Me only');
  echo $form->input('acl.read.preview', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => "Who can view the image?"));
  echo $form->input('acl.read.original', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => "Who can download the image?"));
  echo $form->input('acl.write.tag', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => "Who can add tags?"));
  echo $form->input('acl.write.meta', array('type' => 'select', 'options' => $aclSelect, 'selected' => ACL_LEVEL_KEEP, 'label' => "Who can edit all meta data?"));
  echo $form->input('Group.id', array('type' => 'select', 'options' => $groups, 'selected' => 0, 'label' => "Default image group?"));
?>
</fieldset>
<?php endif; // canWriteAcl==true ?>
<?php echo $form->submit('Apply'); ?>
</form>
<?php endif; // canWriteTag==true ?>
</div>
