<?php
  if ($this->request->data['Media']['canWriteTag']) {
    echo $this->Form->input('Field.keyword', array('label' => __('Tags'), 'value' => join(', ', Set::extract('/Field[name=keyword]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.keyword', '/explorer/autocomplete/tag', array('split' => true));
  }
  if ($this->request->data['Media']['canWriteMeta']) {
    echo $this->Form->input('Field.category', array('label' => __('Categories'), 'value' => join(', ', Set::extract('/Field[name=category]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.category', '/explorer/autocomplete/category', array('split' => true));
    echo $this->Form->input('Field.city', array('label' => __('City'), 'value' => join('', Set::extract('/Field[name=city]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.city', '/explorer/autocomplete/city');
    echo $this->Form->input('Field.sublocation', array('label' => __('Sublocation'), 'value' => join('', Set::extract('/Field[name=sublocation]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.sublocation', '/explorer/autocomplete/sublocation');
    echo $this->Form->input('Field.state', array('label' => __('State'), 'value' => join('', Set::extract('/Field[name=state]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.state', '/explorer/autocomplete/state');
    echo $this->Form->input('Field.country', array('label' => __('Country'), 'value' => join('', Set::extract('/Field[name=country]/data', $this->request->data))));
    echo $this->Autocomplete->autoComplete('Field.country', '/explorer/autocomplete/country');
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
