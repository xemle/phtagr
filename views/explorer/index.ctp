<h1>Explorer</h1>
<?php $session->flash(); ?>
<div class="navigator">
<?php 
echo $query->prev().' '.$query->numbers().' '.$query->next();
?>
</div>

<?php 
$query->initialize();
?>
<div class="thumbs">
<script type="text/javascript">
  var imageData = [];
</script>
<?php
$cell=0;
$canWriteTag=false;
$canWriteMeta=false;
$canWriteAcl=false;
$pos = ($query->get('page', 1)-1) * $query->get('show', 12) + 1;
foreach($data as $image): ?>
<div class="thumb" id="image-<?php echo $image['Image']['id'];?>" >
<script type="text/javascript">
  imageData[<?php echo $image['Image']['id']; ?>] = [];
</script>
<div class="unselected" id="thumb-<?php echo $image['Image']['id']; ?>">
<h2><?php echo $image['Image']['file']; ?></h2>
<div class="image">
<?php 
  $size = $imageData->getimagesize($image, OUTPUT_SIZE_THUMB);
  $query->set('pos', $pos++);
  echo "<a href=\"".Router::url("/explorer/image/".$image['Image']['id'].'/'.$query->getParams())."\">";
  echo "<img src=\"".Router::url("/files/thumb/".$image['Image']['id'])."\" $size[3] alt=\"".$image['Image']['name']."\"/>"; 
  echo "</a>";

  if ($image['Image']['canWriteTag'])
    $canWriteTag=true;
  if ($image['Image']['canWriteMeta'])
    $canWriteMeta=true;
  if ($image['Image']['canWriteAcl'])
    $canWriteAcl=true;
?>
</div>

<div class="meta">
<div id="<?php echo 'meta-'.$image['Image']['id']; ?>">
<table>
  <?php echo $html->tableCells($imageData->metaTable(&$image)); ?>
</table>
</div>
</div><!-- meta -->

</div></div><!-- thumb -->
<?php 
  $cell++;
  if ($cell%2==0)
    echo "<div class=\"row2\" ></div>\n";
  if ($cell%3==0)
    echo "<div class=\"row3\" ></div>\n";
  if ($cell%4==0)
    echo "<div class=\"row4\" ></div>\n";
?>
<?php endforeach; ?>
</div>

<?php if ($canWriteTag): ?>
<div class="navigator">
<a href="javascript:void(0);" onclick="thumbSelectAll();">Select All</a>
<a href="javascript:void(0);" onclick="thumbSelectInvert();">Invert Selection</a>
</div>
<?php endif; ?>

<div class="navigator">
<?php 
echo $query->prev().' '.$query->numbers().' '.$query->next()
?>
</div>

<div class="edit">
<?php if ($canWriteTag): ?>
<? echo $form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$query->getParams())); ?>
<fieldset><legend>Metadata</legend>
<?php echo $form->hidden('Image.ids', array('id' => 'ImageIds')) ?>
<?php 
  if ($canWriteMeta) {
    echo $form->input('Image.date', array('type' => 'text')); 
  }
  echo $form->input('Tags.text', array('label' => 'Tags', 'maxlength' => 320)); 
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
