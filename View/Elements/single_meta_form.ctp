<?php
  if ($this->request->data['Media']['canWriteTag']) {
    echo $this->Form->input('Tag.names', array('label' => __('Tags'), 'value' => join(', ', Set::extract('/Tag/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Tag.names', '/explorer/autocomplete/tag', array('split' => true));
  }
  if ($this->request->data['Media']['canWriteMeta']) {
    echo $this->Form->input('Category.names', array('label' => __('Categories'), 'value' => join(', ', Set::extract('/Category/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Category.names', '/explorer/autocomplete/category', array('split' => true));
    echo $this->Form->input('Location.city', array('label' => __('City'), 'value' => join('', Set::extract('/Location[type='.LOCATION_CITY.']/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Locations.city', '/explorer/autocomplete/city');
    echo $this->Form->input('Location.sublocation', array('label' => __('Sublocation'), 'value' => join('', Set::extract('/Location[type='.LOCATION_SUBLOCATION.']/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Location.sublocation', '/explorer/autocomplete/sublocation');
    echo $this->Form->input('Location.state', array('label' => __('State'), 'value' => join('', Set::extract('/Location[type='.LOCATION_STATE.']/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Location.state', '/explorer/autocomplete/state');
    echo $this->Form->input('Location.country', array('label' => __('Country'), 'value' => join('', Set::extract('/Location[type='.LOCATION_COUNTRY.']/name', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Location.country', '/explorer/autocomplete/country');
    $geo = "";
    if (isset($this->request->data['Media']['latitude']) && isset($this->request->data['Media']['longitude'])) {
      $geo = $this->request->data['Media']['latitude'] . ', ' . $this->request->data['Media']['longitude'];
    }
    echo $this->Form->input('Media.geo', array('label' => __('Geo data'), 'value' => $geo, 'maxlength' => 32));
  }
  if ($this->request->data['Media']['canWriteCaption']) {
  echo $this->Form->input('Media.date', array('type' => 'text', 'after' => $this->Html->tag('div', __('E.g. 2008-08-07 15:30'), array('class' => 'description'))));
  echo $this->Form->input('Media.name', array('type' => 'text'));
  echo $this->Form->input('Media.caption', array('type' => 'text'));
}
?>
