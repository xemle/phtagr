<?php
  $canWriteTag = $canWriteMeta = $canWriteCaption = $canWriteAcl = 0;
  if (count($this->request->data)) {
    $canWriteTag = max(Set::extract('/Media/canWriteTag', $this->request->data));
    $canWriteMeta = max(Set::extract('/Media/canWriteMeta', $this->request->data));
    $canWriteCaption = max(Set::extract('/Media/canWriteCaption', $this->request->data));
    $canWriteAcl = max(Set::extract('/Media/canWriteAcl', $this->request->data));
  }
?>
<div id="p-explorer-menu">
<ul>
  <li id="p-explorer-button-all-meta"><a><?php echo __("View"); ?></a></li>
  <?php if ($canWriteTag): ?>
  <li id="p-explorer-button-meta"><a><?php echo __("Metadata"); ?></a></li>
  <?php if ($canWriteAcl): ?>
  <li id="p-explorer-button-access"><a><?php echo __("Access Right"); ?></a></li>
  <?php endif; // canWriteAcl ?>
  <?php endif; // canWriteTag ?>
  <li id="p-explorer-button-more"><a><?php echo __("Selection"); ?></a></li>
  <li id="p-explorer-button-slideshow"><a><?php echo __("Slideshow"); ?></a></li>
</ul>
<div class="pages">
<ul>
<?php if ($this->Navigator->hasPrev()): ?>
<li><?php echo $this->Navigator->prev(); ?></li>
<?php endif; ?>
<li><?php echo __("Page %d of %d", $this->Navigator->getCurrentPage(), $this->Navigator->getPageCount()); ?></li>
<?php if ($this->Navigator->hasNext()): ?>
<li><?php echo $this->Navigator->next(); ?></li>
<?php endif; ?>
</ul>
</div><!-- pages -->
<div id="p-explorer-menu-content">
<div id="p-explorer-all-meta">
<?php
  $user = $this->Search->getUser();
  $tagUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'tag', array_unique(Set::extract('/Field[name=keyword]/data', $this->request->data)));
  ksort($tagUrls);
  $categoryUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'category', array_unique(Set::extract('/Field[name=category]/data', $this->request->data)));
  ksort($categoryUrls);
  $sublocationUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'sublocation', array_unique(Set::extract('/Field[name=sublocation]/data', $this->request->data)));
  ksort($sublocationUrls);
  $cityUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'city', array_unique(Set::extract('/Field[name=city]/data', $this->request->data)));
  ksort($cityUrls);
  $stateUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'state', array_unique(Set::extract('/Field[name=state]/data', $this->request->data)));
  ksort($stateUrls);
  $countryUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, $user, 'country', array_unique(Set::extract('/Field[name=country]/data', $this->request->data)));
  ksort($countryUrls);

  if (count($tagUrls)) {
    echo "<p>" . __("Tags") . " \n";
    foreach ($tagUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($categoryUrls)) {
    echo "<p>" . __("Categories") . " \n";
    foreach ($categoryUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($sublocationUrls)) {
    echo "<p>" . __("Sublocations") . " \n";
    foreach ($sublocationUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($cityUrls)) {
    echo "<p>" . __("Cities") . " \n";
    foreach ($cityUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($stateUrls)) {
    echo "<p>" . __("States") . " \n";
    foreach ($stateUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
  if (count($countryUrls)) {
    echo "<p>" . __("Countries") . " \n";
    foreach ($countryUrls as $name => $urls) {
      echo $this->ImageData->getExtendSearchLinks($urls, $name) . "\n";
    }
    echo "</p>\n";
  }
?>
<p><?php echo __('Users') . " "; ?>
<?php
  $userUrls = $this->ImageData->getAllExtendSearchUrls($crumbs, false, 'user', array_unique(Set::extract('/User/username', $this->request->data)));
  foreach ($userUrls as $name => $urls) {
    echo $this->ImageData->getExtendSearchLinks($urls, $name, ($name == $user)) . ' ';
  }
?></p>
<p><?php echo __('Pagesize') . " "; ?>
<?php  $links = array();
  foreach (array(6, 12, 24, 60, 120, 240) as $size) {
    $links[] = $this->Html->link($size, $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'show', $size)));
  }
  echo implode($links);
?></p>
<p><?php echo __('Sort') . " "; ?>
<?php  $links = array();
  $links[] = $this->Html->link(__('newest'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'newest')));
  $links[] = $this->Html->link(__('date'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'date')));
  $links[] = $this->Html->link(__('date (reverse)'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', '-date')));
  $links[] = $this->Html->link(__('name'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'name')));
  $links[] = $this->Html->link(__('modified'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'changes')));
  $links[] = $this->Html->link(__('view count'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'viewed')));
  $links[] = $this->Html->link(__('random'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'sort', 'random')));
  echo implode(' ', $links);
?></p>
<p><?php echo __('View') . " "; ?>
<?php  $links = array();
  $links[] = $this->Html->link(__('default'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'view', 'default')));
  $links[] = $this->Html->link(__('compact'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'view', 'compact')));
  $links[] = $this->Html->link(__('small'), $this->Breadcrumb->crumbUrl($this->Breadcrumb->replace($crumbs, 'view', 'small')));
  echo implode(' ', $links);
?></p>
</div><!-- all meta -->
<?php
  $url = $this->Breadcrumb->params($crumbs);
  echo $this->Form->create(null, array('id' => 'explorer', 'action' => 'edit/'.$url, 'class' => 'explorer-menu'));
?>
<div id="p-explorer-edit-meta">
<fieldset><legend><?php echo __("Metadata"); ?></legend>
<?php
  echo $this->Form->hidden('Media.ids', array('id' => 'MediaIds'));
  if ($canWriteTag) {
    echo $this->Form->input('Field.keyword', array('label' => __('Tags'), 'after' => $this->Html->tag('div', __('E.g. newtag, -oldtag'), array('class' => 'description'))));
    echo $this->Autocomplete->autoComplete('Field.keyword', 'autocomplete/tag', array('split' => true));
  }
  if ($canWriteMeta) {
    echo $this->Form->input('Field.category', array('label' => __('Categories')));
    echo $this->Autocomplete->autoComplete('Field.category', 'autocomplete/category', array('split' => true));
    echo $this->Form->input('Field.city', array('label' => __('City')));
    echo $this->Autocomplete->autoComplete('Field.city', 'autocomplete/city');
    echo $this->Form->input('Field.sublocation', array('label' => __('Sublocation')));
    echo $this->Autocomplete->autoComplete('Field.sublocation', 'autocomplete/sublocation');
    echo $this->Form->input('Field.state', array('label' => __('State')));
    echo $this->Autocomplete->autoComplete('Field.state', 'autocomplete/state');
    echo $this->Form->input('Field.country', array('label' => __('Country')));
    echo $this->Autocomplete->autoComplete('Field.country', 'autocomplete/country');
    echo $this->Form->input('Media.geo', array('label' => __('Geo data'), 'maxlength' => 32, 'after' => $this->Html->tag('div', __('latitude, longitude'), array('class' => 'description'))));
  }
  if ($canWriteCaption) {
    echo $this->Form->input('Media.date', array('type' => 'text', 'after' => $this->Html->tag('div', __('E.g. 2008-08-07 15:30'), array('class' => 'description'))));
    echo $this->Form->input('Media.name', array('type' => 'text'));
    echo $this->Form->input('Media.caption', array('type' => 'textarea'));
    $rotations = array(
        '0' => __("Keep"),
        '90' => __("90 CW"),
        '180' => __("180 CW"),
        '270' => __("90 CCW"),
        'reset' => __("Reset")
    );
    echo $this->Html->tag('div', $this->Html->tag('label', __("Rotate")) .
            $this->Html->tag('div', $this->Form->radio('Media.rotation', $rotations, array('legend' => false, 'value' => '0')), array('escape' => false, 'class' => 'radioSet')), array('escape' => false, 'class' => 'input radio'));
  }
?>
</fieldset>
<?php echo $this->Form->submit(__('Apply')); ?>
</div>
<?php if ($canWriteAcl): ?>
<div id="p-explorer-edit-access">
<fieldset><legend><?php echo __("Access Rights"); ?></legend>
<?php
  $aclSelect = array(
    ACL_LEVEL_PRIVATE => __('Me'),
    ACL_LEVEL_GROUP => __('Group'),
    ACL_LEVEL_USER => __('Users'),
    ACL_LEVEL_OTHER => __('All'),
    ACL_LEVEL_KEEP => __('Keep'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can view the image")).
    $this->Html->tag('div', $this->Form->radio('Media.readPreview', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can download the image?")).
    $this->Html->tag('div', $this->Form->radio('Media.readOriginal', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can add tags?")).
    $this->Html->tag('div', $this->Form->radio('Media.writeTag', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Html->tag('div',
    $this->Html->tag('label', __("Who can edit all meta data?")).
    $this->Html->tag('div', $this->Form->radio('Media.writeMeta', $aclSelect, array('legend' => false, 'value' => ACL_LEVEL_KEEP)), array('escape' => false, 'class' => 'radioSet')),
    array('escape' => false, 'class' => 'input radio'));
  echo $this->Form->input('Group.names', array('value' => '', 'label' => __("Media access group")));
  echo $this->Autocomplete->autoComplete('Group.names', '/explorer/autocomplete/aclgroup', array('split' => true));
?>
</fieldset>
<?php echo $this->Form->submit(__('Apply')); ?>
</div>
<?php endif; // canWriteAcl==true ?>
<?php echo $this->Form->end(); ?>
<div id="p-explorer-more">
<p><?php
  echo __('Download:') . ' ';
  echo $this->Html->link(__("Original"), 'javascript:void', array('id' => 'p-explorer-download-original', 'title' => __('Download original files from selected media')));
  echo ' '.$this->Html->link(__("High Resolution"), 'javascript:void', array('id' => 'p-explorer-download-high', 'title' => __('Download high resolution files (%d px) from selected media', OUTPUT_SIZE_HIGH)));
  echo ' '.$this->Html->link(__("Preview Resolution"), 'javascript:void', array('id' => 'p-explorer-download-preview', 'title' => __('Download preview files (%d px) from selected media', OUTPUT_SIZE_PREVIEW)));
?></p>
<p><?php
  echo __('Media:') . ' ';
  echo $this->Html->link(__("Remove"), 'javascript:void', array('id' => 'p-explorer-selection-remove', 'title' => __('Remove Media from database')));
  echo $this->Html->link(__("Clear Cache"), 'javascript:void', array('id' => 'p-explorer-selection-delete-cache', 'title' => __('Clear media cache files')));
  echo $this->Html->link(__("Sync"), 'javascript:void', array('id' => 'p-explorer-selection-sync', 'title' => __('Synchorinze meta data')));
?></p>
</div>
</div>
</div><!-- explorer menu -->
<div id="p-explorer-menu-space"></div>
