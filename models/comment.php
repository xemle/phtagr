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
    'name' => array('rule' => 'alphaNumeric', 'message' => 'Name must only contain letters and numbers.'),
    'email' => array('rule' => 'email', 'message' => 'Email is invalid'));

}
?>
