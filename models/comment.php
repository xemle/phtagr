<?php
class Comment extends AppModel {

  var $name = 'Comment';

  //The Associations below have been created with all possible keys, those that are not needed can be removed
  var $belongsTo = array(
      'Image' => array('className' => 'Image',
                'foreignKey' => 'image_id',
                'conditions' => '',
                'fields' => '',
                'order' => ''
      ),
      'User' => array('className' => 'User',
                'foreignKey' => 'user_id',
                'conditions' => '',
                'fields' => '',
                'order' => ''
      )
  );

  var $validate = array(
    'name' => array('rule' => 'notEmpty', 'message' => 'Name is missing'),
    'email' => array('rule' => 'email', 'message' => 'Email is invalid'),
    'text' => array(
      'empty' => array('rule' => 'notEmpty', 'message' => 'Comment text is empty'),
      'max' => array('rule' => array('maxLength', 2048), 'message' => 'Comment is to long')
      )
    );

}
?>
