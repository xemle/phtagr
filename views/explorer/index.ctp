<h1><?php __('Explorer'); ?></h1>
<?php echo $session->flash(); ?>

<?php 
  $search->initialize();
?>
<?php
  echo $breadcrumb->breadcrumb($crumbs);
?>
<?php if ($navigator->hasPages()): ?>
<div class="paginator"><div class="subpaginator">
<?php echo $navigator->prev().' '.$navigator->numbers().' '.$navigator->next(); ?>
</div></div>
<?php endif; ?>

<div class="p-explorer-cells">
<?php
$cell=0;
$pos = ($search->getPage(1)-1) * $search->getShow(12) + 1;
$canWriteTag = max(Set::extract('/Media/canWriteTag', $this->data));
$canWriteMeta = max(Set::extract('/Media/canWriteMeta', $this->data));
$canWriteAcl = max(Set::extract('/Media/canWriteAcl', $this->data));

echo $javascript->codeBlock("var mediaIds = [" . implode(', ', Set::extract('/Media/id', $this->data)) . "];");

foreach ($this->data as $media): ?>

<div class="p-explorer-cell unselected" id="media-<?php echo $media['Media']['id']; ?>">
<h2><?php echo $media['Media']['name']; ?></h2>

<?php 
  $size = $imageData->getimagesize($media, OUTPUT_SIZE_THUMB);
  $imageCrumbs = $this->Breadcrumb->replace($crumbs, 'page', $search->getPage());
  $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'pos', $pos++);
  if ($search->getShow(12) != 12) {
    $imageCrumbs = $this->Breadcrumb->replace($imageCrumbs, 'show', $search->getShow());
  }
  
  // image centrering from http://www.brunildo.org/test/img_center.html
  echo '<div class="p-explorer-cell-image"><span></span>';
  echo $html->tag('a',
    $html->tag('img', false, array(
      'src' => Router::url("/media/thumb/".$media['Media']['id']),
      'width' => $size[0], 'height' => $size[1], 
      'alt' => $media['Media']['name'])),
    array('href' => Router::url("/images/view/".$media['Media']['id'].'/'.$breadcrumb->params($imageCrumbs))));
  echo "</div>";
?>
<div class="p-explorer-cell-caption">
<?php
  if (!$search->getUser() || $search->getUser() != $session->read('User.username')) {
    printf(__("by %s", true), $html->link($media['User']['username'], "/explorer/user/".$media['User']['username']));
  }
  $extra = array();
  if ($media['Media']['clicks'] > 0) {
    $extra[] = sprintf("%d %s", $media['Media']['clicks'], $html->image('icons/eye.png', array('alt' => __('Views', true), 'title' => sprintf(__("%d views", true), $media['Media']['clicks']))));
  }
  if (count($media['Comment'])) {
    $extra[] = sprintf(__("%d %s", true), count($media['Comment']), $html->image('icons/comments.png', array('alt' => __('Comments', true), 'title' => sprintf(__("%d comments", true), count($media['Comment'])))));
  }
  if ($extra) {
    echo " (".implode(', ', $extra).")";
  }
?>
</div>

<div class="p-explorer-cell-meta" id="<?php echo 'meta-'.$media['Media']['id']; ?>">
<table>
  <?php echo $html->tableCells($imageData->metaTable(&$media)); ?>
</table>
</div><!-- meta -->
</div><!-- cell -->

<?php 
  for ($i = 2; $i <= 6; $i++) {
    if ($cell % $i == 0) {
      echo $html->tag('span', false, array('class' => "cell-row-$i"));
    }
  }
?>
<?php $cell++; endforeach; ?>
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

  $url = $breadcrumb->params($crumbs);
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
