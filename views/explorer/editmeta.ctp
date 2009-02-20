<?php 
  $mediumId = $data['Medium']['id'];
  echo $ajax->form('savemeta/'.$mediumId, 'post', array('url' => '/explorer/savemeta/'.$mediumId, 'update' => 'meta-'.$mediumId, 'id' => 'form-meta-'.$mediumId)); 
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

  echo $form->input('Medium.date', array('type' => 'text', 'value' => $data['Medium']['date']));
  echo $form->input('Tags.text', array('value' => $tagText, 'label' => 'Tags'));
  echo $form->input('Categories.text', array('value' => $categoryText, 'label' => 'Cagegories'));
  echo $form->input('Locations.city', array('value' => $locations[LOCATION_CITY]));
  echo $form->input('Locations.sublocation', array('value' => $locations[LOCATION_SUBLOCATION]));
  echo $form->input('Locations.state', array('value' => $locations[LOCATION_STATE]));
  echo $form->input('Locations.country', array('value' => $locations[LOCATION_COUNTRY]));
?>
</fieldset>
<?php
  echo $form->submit('Save', array('div' => false)); 
  echo $ajax->link('Cancel', '/explorer/updatemeta/'.$mediumId, array('update' => 'meta-'.$mediumId, 'class' => 'reset'));
?>
</form>
