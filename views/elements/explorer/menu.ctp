<?php
  $canWriteTag = $canWriteMeta = $canWriteAcl = 0;
  if (count($this->data)) {
    $canWriteTag = max(Set::extract('/Media/canWriteTag', $this->data));
    $canWriteMeta = max(Set::extract('/Media/canWriteMeta', $this->data));
    $canWriteAcl = max(Set::extract('/Media/canWriteAcl', $this->data));
  }
?>
<div id="p-explorer-menu">
<ul>
  <li id="p-explorer-button-all-meta"><a><?php __("Show Metadata"); ?></a></li>
  <?php if ($canWriteTag): ?>
  <li id="p-explorer-button-meta"><a><?php __("Edit Metadata"); ?></a></li>
  <?php if ($canWriteAcl): ?>
  <li id="p-explorer-button-access"><a><?php __("Edit Access Rights"); ?></a></li>
  <?php endif; // canWriteAcl ?>
  <?php endif; // canWriteTag ?>
</ul>
<div class="pages">
<ul>
<?php if ($navigator->hasPrev()): ?>
<li><?php echo $navigator->prev(); ?></li>
<?php endif; ?>
<li><?php printf(__("Page %d of %d", true), $navigator->getCurrentPage(), $navigator->getPageCount()); ?></li>
<?php if ($navigator->hasNext()): ?>
<li><?php echo $navigator->next(); ?></li>
<?php endif; ?>
</ul>
</div><!-- pages -->
<div id="p-explorer-menu-content">
<div id="p-explorer-all-meta">
<?php
  $user = $search->getUser();
  $tagUrls = $imageData->getAllExtendSearchUrls($crumbs, $user, 'tag', array_unique(Set::extract('/Tag/name', $this->data)));
  ksort($tagUrls);
  $categoryUrls = $imageData->getAllExtendSearchUrls($crumbs, $user, 'category', array_unique(Set::extract('/Category/name', $this->data)));
  ksort($categoryUrls);
  $locationUrls = $imageData->getAllExtendSearchUrls($crumbs, $user, 'location', array_unique(Set::extract('/Location/name', $this->data)));
  ksort($locationUrls);

  if (count($tagUrls)) {
    echo "<p>" . __("Tags", true) . " \n";
    foreach ($tagUrls as $name => $urls) {
      echo $imageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($categoryUrls)) {
    echo "<p>" . __("Categories", true) . " \n";
    foreach ($categoryUrls as $name => $urls) {
      echo $imageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($locationUrls)) {
    echo "<p>" . __("Locations", true) . " \n";
    foreach ($locationUrls as $name => $urls) {
      echo $imageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
?>
<p><?php echo __('Users', true) . " "; ?>
<?php
  $userUrls = $imageData->getAllExtendSearchUrls($crumbs, false, 'user', array_unique(Set::extract('/User/username', $this->data)));
  foreach ($userUrls as $name => $urls) {
    echo $imageData->getExtendSearchLinks($urls, $name, ($name == $user)) . ' ';
  }
?></p>
<p><?php echo __('Pagesize', true) . " "; ?>
<?php  $links = array();
  foreach (array(6, 12, 24, 60, 120, 240) as $size) {
    $links[] = $html->link($size, $breadcrumb->crumbUrl($breadcrumb->replace($crumbs, 'show', $size)));
  }
  echo implode($links);
?></p>
</div><!-- all meta -->
<?php 
  $url = $breadcrumb->params($crumbs);
  echo $form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$url, 'class' => 'explorer-menu'));
?>
<div id="p-explorer-edit-meta">
<fieldset><legend><?php __("Metadata"); ?></legend>
<?php 
  echo $form->hidden('Media.ids', array('id' => 'MediaIds'));
  if ($canWriteMeta) {
    echo $form->input('Media.date', array('type' => 'text', 'after' => $html->tag('div', __('E.g. 2008-08-07 15:30', true), array('class' => 'description')))); 
  }
  echo $form->input('Tags.text', array('label' => __('Tags', true), 'after' => $html->tag('div', __('E.g. newtag, -oldtag', true), array('class' => 'description'))));
  echo $autocomplete->autoComplete('Tags.text', 'autocomplete/tag', array('split' => true));
  if ($canWriteMeta) {
    echo $form->input('Categories.text', array('label' => __('Categories', true)));
    echo $autocomplete->autoComplete('Categories.text', 'autocomplete/category', array('split' => true));
    echo $form->input('Locations.city', array('label' => __('City', true)));
    echo $autocomplete->autoComplete('Locations.city', 'autocomplete/city');
    echo $form->input('Locations.sublocation', array('label' => __('Sublocation', true)));
    echo $autocomplete->autoComplete('Locations.sublocation', 'autocomplete/sublocation');
    echo $form->input('Locations.state', array('label' => __('State', true)));
    echo $autocomplete->autoComplete('Locations.state', 'autocomplete/state');
    echo $form->input('Locations.country', array('label' => __('Country', true)));
    echo $autocomplete->autoComplete('Locations.country', 'autocomplete/country');
    echo $form->input('Media.geo', array('label' => __('Geo data', true), 'maxlength' => 32, 'after' => $html->tag('div', __('latitude, longitude', true), array('class' => 'description'))));
  }
?>
</fieldset>
<?php echo $form->submit(__('Apply', true)); ?>
</div>
<?php if ($canWriteAcl): ?>
<div id="p-explorer-edit-access">
<fieldset><legend><?php __("Access Rights"); ?></legend>
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
<?php echo $form->submit(__('Apply', true)); ?>
</div>
<?php endif; // canWriteAcl==true ?>
<?php echo $form->end(); ?>
</div>
</div><!-- explorer menu -->
<div id="p-explorer-menu-space"></div>
