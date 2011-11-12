<?php 
  $mediaId = $this->data['Media']['id'];
  echo $form->create(null, array('url' => 'savemeta/'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  if ($this->data['Media']['canWriteTag']) {
    echo $form->input('Tag.names', array('label' => __('Tags', true), 'value' => join(', ', Set::extract('/Tag/name', $this->data))));
    echo $autocomplete->autoComplete('Tag.names', 'autocomplete/tag', array('split' => true));
  }
  if ($this->data['Media']['canWriteMeta']) {
    echo $form->input('Category.names', array('label' => __('Categories', true), 'value' => join(', ', Set::extract('/Category/name', $this->data))));
    echo $autocomplete->autoComplete('Category.names', 'autocomplete/category', array('split' => true));
    echo $form->input('Location.city', array('label' => __('City', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_CITY.']/name', $this->data))));
    echo $autocomplete->autoComplete('Locations.city', 'autocomplete/city');
    echo $form->input('Location.sublocation', array('label' => __('Sublocation', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_SUBLOCATION.']/name', $this->data))));
    echo $autocomplete->autoComplete('Location.sublocation', 'autocomplete/sublocation');
    echo $form->input('Location.state', array('label' => __('State', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_STATE.']/name', $this->data))));
    echo $autocomplete->autoComplete('Location.state', 'autocomplete/state');
    echo $form->input('Location.country', array('label' => __('Country', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_COUNTRY.']/name', $this->data))));
    echo $autocomplete->autoComplete('Location.country', 'autocomplete/country');
    $geo = "";
    if (isset($this->data['Media']['latitude']) && isset($this->data['Media']['longitude'])) {
      $geo = $this->data['Media']['latitude'] . ', ' . $this->data['Media']['longitude'];
    }
    echo $form->input('Media.geo', array('label' => __('Geo data', true), 'value' => $geo, 'maxlength' => 32));
  }
  if ($this->data['Media']['canWriteCaption']) {
  echo $form->input('Media.date', array('type' => 'text', 'after' => $html->tag('div', __('E.g. 2008-08-07 15:30', true), array('class' => 'description'))));
  echo $form->input('Media.name', array('type' => 'text'));
  echo $form->input('Media.caption', array('type' => 'text'));
}
?>
</fieldset>
</form>
