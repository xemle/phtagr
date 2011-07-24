<?php 
  $mediaId = $data['Media']['id'];
  echo $form->create(null, array('url' => 'savemeta/'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  echo $form->input('Media.date', array('type' => 'text', 'value' => $data['Media']['date'], 'label' => __("Date", true)));
  echo $form->input('Tags.text', array('label' => __('Tags', true), 'value' => join(', ', Set::extract('/Tag/name', $data))));
  echo $autocomplete->autoComplete('Tags.text', 'autocomplete/tag', array('split' => true));
  echo $form->input('Categories.text', array('label' => __('Categories', true), 'value' => join(', ', Set::extract('/Category/name', $data))));
  echo $autocomplete->autoComplete('Categories.text', 'autocomplete/category', array('split' => true));
  echo $form->input('Locations.city', array('label' => __('City', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_CITY.']/name', $data))));
  echo $autocomplete->autoComplete('Locations.city', 'autocomplete/city');
  echo $form->input('Locations.sublocation', array('label' => __('Sublocation', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_SUBLOCATION.']/name', $data))));
  echo $autocomplete->autoComplete('Locations.sublocation', 'autocomplete/sublocation');
  echo $form->input('Locations.state', array('label' => __('State', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_STATE.']/name', $data))));
  echo $autocomplete->autoComplete('Locations.state', 'autocomplete/state');
  echo $form->input('Locations.country', array('label' => __('Country', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_COUNTRY.']/name', $data))));
  echo $autocomplete->autoComplete('Locations.country', 'autocomplete/country');
  echo $form->input('Media.geo', array('label' => __('Geo data', true), 'maxlength' => 32));
?>
</fieldset>
</form>
