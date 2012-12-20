
<div id="category-cloud" style="width:930px;">
<h3><?php echo __("Categories"); ?></h3>
<div class="cloud" style="width:900px;">
<?php
if (isset($cloudCategories) && count($cloudCategories)) {
  echo $this->Cloud->cloud($cloudCategories, '/explorer/category/');
} else {
  echo '<p>' . __("No categories assigned") . '</p>';
}
?>
</div></div>

<div id="tag-cloud" style="width:930px;" >
   
<h3><?php echo __("Tags"); ?></h3>
<div class="cloud" style="width:900px;">
<?php
if (isset($cloudTags) && count($cloudTags)) {
  echo $this->Cloud->cloud($cloudTags, '/explorer/tag/');
} else {
  echo '<p>' . __("No tags assigned") . '</p>';
}
?>
</div></div>

