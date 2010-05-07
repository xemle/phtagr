<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savemeta/'.$mediaId, 'post', array('url' => '/explorer/savemeta/'.$mediaId, 'update' => 'meta-'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  $tags = Set::extract($data, "Tag.{n}.name");
  $tagText = implode(', ', $tags);
  $categories = Set::extract($data, "Category.{n}.name");
  $categoryText = implode(', ', $categories);
  $locations = array(LOCATION_CITY => '', LOCATION_SUBLOCATION => '', LOCATION_STATE => '', LOCATION_COUNTRY => '');
  foreach ($data['Location'] as $location)
    $locations[$location['type']] = $location['name'];

  echo $form->input('Media.date', array('type' => 'text', 'value' => $data['Media']['date'], 'label' => __("Date", true)));
  echo $form->input('Tags.text', array('value' => $tagText, 'label' => __('Tags', true)));
  echo $form->input('Categories.text', array('value' => $categoryText, 'label' => __('Categories', true)));
  echo $form->input('Locations.city', array('value' => $locations[LOCATION_CITY], 'label' => __('City', true)));
  echo $form->input('Locations.sublocation', array('value' => $locations[LOCATION_SUBLOCATION], 'label' => __('Sublocation', true)));
  echo $form->input('Locations.state', array('value' => $locations[LOCATION_STATE], 'label' => __('State', true)));
  echo $form->input('Locations.country', array('value' => $locations[LOCATION_COUNTRY], 'label' => __('Country', true)));
  if ($data['Media']['latitude'] || $data['Media']['longitude']) {
    $geo = $data['Media']['latitude'].', '.$data['Media']['longitude'];
  } else {
    $geo = '';
  }
  echo $form->input('Media.geo', array('value' => $geo, 'label' => __('Geo data', true)));
?>
</fieldset>
<?php
  echo $form->submit(__('Save', true), array('div' => false)); 
  echo $ajax->link(__('Cancel', true), '/explorer/updatemeta/'.$mediaId, array('update' => 'meta-'.$mediaId, 'class' => 'reset'));
?>
</form>
