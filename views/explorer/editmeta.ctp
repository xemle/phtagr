<?php 
  $mediaId = $data['Media']['id'];
  echo $ajax->form('savemeta/'.$mediaId, 'post', array('url' => '/explorer/savemeta/'.$mediaId, 'update' => 'meta-'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  $locations = array(LOCATION_CITY => '', LOCATION_SUBLOCATION => '', LOCATION_STATE => '', LOCATION_COUNTRY => '');
  foreach ($data['Location'] as $location) {
    $locations[$location['type']] = $location['name'];
  }

  echo $form->input('Media.date', array('type' => 'text', 'value' => $data['Media']['date'], 'label' => __("Date", true)));
  echo $html->tag('div',
    $form->label('Tags.text', __('Tags', true)).
    $ajax->autoComplete('Tags.text', 'autocomplete/tag', array('value' => implode(', ', Set::extract('/Tag/name', $data)), 'tokens' => ',')), 
    array('class' => 'input text'));
  echo $html->tag('div',
    $form->label('Categories.text', __('Categories', true)).
    $ajax->autoComplete('Categories.text', 'autocomplete/category', array('value' => implode(', ', Set::extract('/Category/name', $data)), 'tokens' => ',')), 
    array('class' => 'input text'));
  echo $html->tag('div',
    $form->label('Locations.city', __('City', true)).
    $ajax->autoComplete('Locations.city', 'autocomplete/city', array('value' => $locations[LOCATION_CITY])), 
    array('class' => 'input text'));
  echo $html->tag('div',
    $form->label('Locations.sublocation', __('Sublocation', true)).
    $ajax->autoComplete('Locations.sublocation', 'autocomplete/sublocation', array('value' => $locations[LOCATION_SUBLOCATION])), 
    array('class' => 'input text'));
  echo $html->tag('div',
    $form->label('Locations.state', __('State', true)).
    $ajax->autoComplete('Locations.state', 'autocomplete/state', array('value' => $locations[LOCATION_STATE])), 
    array('class' => 'input text'));
  echo $html->tag('div',
    $form->label('Locations.country', __('Country', true)).
    $ajax->autoComplete('Locations.country', 'autocomplete/country', array('value' => $locations[LOCATION_COUNTRY])), 
    array('class' => 'input text'));
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
