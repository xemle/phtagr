<?php
class Comment extends AppModel {

  var $name = 'Comment';

  //The Associations below have been created with all possible keys, those that are not needed can be removed
  var $belongsTo = array(
      'Medium' => array('foreignKey' => 'medium_id'),
      'User' => array()
  );

  var $validate = array(
    'name' => array('rule' => 'notEmpty', 'message' => 'Name is missing'),
    'email' => array('rule' => 'email', 'message' => 'Email is invalid'),
    'url' => array('rule' => 'url', 'message' => 'URL is invalid', 'required' => false, 'allowEmpty' => true),
    'text' => array(
      'empty' => array('rule' => 'notEmpty', 'message' => 'Comment text is empty'),
      'max' => array('rule' => array('maxLength', 2048), 'message' => 'Comment is to long')
      )
    );

  function beforeSave() {
    // Add http:// prefix if no protocol is found
    if (!empty($this->data['Comment']['url']) && strpos('://', $this->data['Comment']['url']) < 7) {
      $this->data['Comment']['url'] = 'http://'.$this->data['Comment']['url'];
    }
    return true;
  }
}
?>
