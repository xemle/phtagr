<?php 
  $mediaId = $this->data['Media']['id'];
  echo $form->create(null, array('url' => 'savemeta/'.$mediaId, 'id' => 'form-meta-'.$mediaId)); 
?>
<fieldset>
<?php
  if ($this->data['Media']['canWriteTag']) {
    echo $form->input('Tag.names', array('label' => __('Tags', true), 'value' => join(', ', Set::extract('/Tag/name', $this->data))));
    echo $autocomplete->autoComplete('Tags.text', 'autocomplete/tag', array('split' => true));
  }
  if ($this->data['Media']['canWriteMeta']) {
    echo $form->input('Category.names', array('label' => __('Categories', true), 'value' => join(', ', Set::extract('/Category/name', $this->data))));
    echo $autocomplete->autoComplete('Categories.text', 'autocomplete/category', array('split' => true));
    echo $form->input('Location.city', array('label' => __('City', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_CITY.']/name', $this->data))));
    echo $autocomplete->autoComplete('Locations.city', 'autocomplete/city');
    echo $form->input('Location.sublocation', array('label' => __('Sublocation', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_SUBLOCATION.']/name', $this->data))));
    echo $autocomplete->autoComplete('Locations.sublocation', 'autocomplete/sublocation');
    echo $form->input('Location.state', array('label' => __('State', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_STATE.']/name', $this->data))));
    echo $autocomplete->autoComplete('Locations.state', 'autocomplete/state');
    echo $form->input('Location.country', array('label' => __('Country', true), 'value' => join('', Set::extract('/Location[type='.LOCATION_COUNTRY.']/name', $this->data))));
    echo $autocomplete->autoComplete('Locations.country', 'autocomplete/country');
    echo $form->input('Media.geo', array('label' => __('Geo data', true), 'maxlength' => 32));
  }
  if ($this->data['Media']['canWriteCaption']) {
  echo $form->input('Media.date', array('type' => 'text', 'after' => $html->tag('div', __('E.g. 2008-08-07 15:30', true), array('class' => 'description'))));
  echo $form->input('Media.name', array('type' => 'text'));
  echo $form->input('Media.caption', array('type' => 'text'));
  $rotations = array(
      '0' => __("Keep", true),
      '90' => __("90 CW", true),
      '180' => __("180 CW", true),
      '270' => __("90 CCW", true)
  );
  echo $html->tag('div', $html->tag('label', __("Rotate", true)) .
          $html->tag('div', $form->radio('Media.rotation', $rotations, array('legend' => false, 'value' => '0')), array('escape' => false, 'class' => 'radioSet')), array('escape' => false, 'class' => 'input radio'));
}
?>
</fieldset>
</form>
