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

<?php if ($query->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $query->prev().' '.$query->numbers().' '.$query->next(); ?>
</div></div>
<?php endif; ?>

<div class="thumbs">
<script type="text/javascript">
  var mediaData = [];
</script>
<?php
$query->initialize();
$cell=0;
$canWriteTag=false;
$canWriteMeta=false;
$canWriteAcl=false;
$pos = ($query->get('page', 1)-1) * $query->get('show', 12) + 1;
foreach($data as $medium): ?>
<?php $side = $cell % 2 ? 'r' : 'l'; ?>
<?php if (!($cell % 2)): ?><div class="subcolumns"><?php endif; ?>
<div class="c50<?=$side; ?>"><div class="subc<?=$side; ?> unselected thumb" id="medium-<?= $medium['Medium']['id'];?>" >
<script type="text/javascript">
  mediaData[<?php echo $medium['Medium']['id']; ?>] = [];
</script>
<h2><?php echo $medium['Medium']['name']; ?></h2>
<div class="image">
<?php 
  $size = $imageData->getimagesize($medium, OUTPUT_SIZE_THUMB);
  $query->set('pos', $pos++);
  echo "<a href=\"".Router::url("/images/view/".$medium['Medium']['id'].'/'.$query->getParams())."\">";
  echo "<img src=\"".Router::url("/media/thumb/".$medium['Medium']['id'])."\" $size[3] alt=\"".$medium['Medium']['name']."\"/>"; 
  echo "</a>";

  if ($medium['Medium']['canWriteTag'])
    $canWriteTag=true;
  if ($medium['Medium']['canWriteMeta'])
    $canWriteMeta=true;
  if ($medium['Medium']['canWriteAcl'])
    $canWriteAcl=true;
?>
</div>

<?php 
  if (!$query->get('mymedium')): ?>
<div class="user">
<?php
  echo $html->link($medium['User']['username'], "/explorer/user/".$medium['User']['id']);
?>
</div>
<?php endif; ?>

<div class="meta">
<div id="<?php echo 'meta-'.$medium['Medium']['id']; ?>">
<table>
  <?php echo $html->tableCells($imageData->metaTable(&$medium)); ?>
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

<?php if ($query->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $query->prev().' '.$query->numbers().' '.$query->next(); ?>
</div></div>
<?php endif; ?>

<div class="edit">
<?php if ($canWriteTag): ?>
<?php 
  $query->initialize();
  echo $form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$query->getParams())); 
?>
<fieldset><legend>Metadata</legend>
<?php echo $form->hidden('Medium.ids', array('id' => 'MediumIds')) ?>
<?php 
  if ($canWriteMeta) {
    echo $form->input('Medium.date', array('type' => 'text', 'after' => '<span class="hint">E.g. 2008-08-07 15:30</span>')); 
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
