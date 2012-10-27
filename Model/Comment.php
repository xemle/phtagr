<?php
class Comment extends AppModel {

  var $name = 'Comment';

  //The Associations below have been created with all possible keys, those that are not needed can be removed
  var $belongsTo = array(
      'Media' => array(),
      'User' => array()
  );

  var $currentUser = array();

  var $validate = array(
    'name' => array('rule' => 'notEmpty', 'message' => 'Name is missing'),
    'email' => array('rule' => 'email', 'message' => 'Email is invalid'),
    'url' => array('rule' => 'url', 'message' => 'URL is invalid', 'required' => false, 'allowEmpty' => true),
    'text' => array(
      'empty' => array('rule' => 'notEmpty', 'message' => 'Comment text is empty'),
      'max' => array('rule' => array('maxLength', 2048), 'message' => 'Comment is to long')
      )
    );

  public function beforeSave($options = array()) {
    // Add http:// prefix if no protocol is found
    if (!empty($this->data['Comment']['url']) && !preg_match('/^https?:\/\//i', $this->data['Comment']['url'])) {
      $this->data['Comment']['url'] = 'http://'.$this->data['Comment']['url'];
    }
    return true;
  }

  public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
    $query = am(array(
        'fields' => $fields,
        'conditions' => $conditions,
        'order' => $order,
        'limit' => $limit,
        'page' => $page,
        'recursive' => $recursive,
        'group' => 'Comment.id'), $extra);
    $aclQuery = $this->Media->buildAclQuery($this->currentUser);
    if (count($aclQuery['joins'])) {
      $joinConditions = "`{$this->alias}`.`media_id` = `{$this->Media->alias}`.`{$this->Media->primaryKey}`";
      $join = array(
          'table' => $this->Media,
          'alias' => $this->Media->alias,
          'type' => 'LEFT',
          'conditions' => $joinConditions
      );
      $aclQuery['joins'] = am(array($join), $aclQuery['joins']);
    }
    return $this->find('all', am($query, $aclQuery));
  }

  public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
    $query = am(array(
        'fields' => 'DISTINCT Comment.id',
        'conditions' => $conditions,
        'recursive' => $recursive), $extra);
    $aclQuery = $this->Media->buildAclQuery($this->currentUser);
    if (count($aclQuery['joins'])) {
      $joinConditions = "`{$this->alias}`.`media_id` = `{$this->Media->alias}`.`{$this->Media->primaryKey}`";
      $join = array(
          'table' => $this->Media,
          'alias' => $this->Media->alias,
          'type' => 'LEFT',
          'conditions' => $joinConditions
      );
      $aclQuery['joins'] = am(array($join), $aclQuery['joins']);
    }
    return $this->find('count', am($query, $aclQuery));
  }
}
?>
