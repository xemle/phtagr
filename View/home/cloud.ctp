<h3><?php echo __("Categories"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudCategories) && count($cloudCategories)) {
  echo $this->Cloud->cloud($cloudCategories, '/explorer/category/');
} else {
  echo '<p>' . __("No categories assigned") . '</p>';
}
?>
</div>
   
<h3><?php echo __("Tags"); ?></h3>
<div class="cloud">
<?php
if (isset($cloudTags) && count($cloudTags)) {
  echo $this->Cloud->cloud($cloudTags, '/explorer/tag/');
} else {
  echo '<p>' . __("No tags assigned") . '</p>';
}
?>
</div>
