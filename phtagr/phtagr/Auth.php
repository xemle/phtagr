<?php

global $prefix;

class Auth 
{

var $is_auth; 
var $is_logout; 
var $user; 
var $userid;
var $root;

function Auth()
{
  $this->clear_data();
}

function clear_data()
{
  $this->is_auth=false;
  $this->is_logout=false;
  $this->user='';
  $this->userid='';
  $this->userid='';
  $this->root='/var/tmp';
}
function check_login($user, $password)
{
    global $db;
    if ($user!='' && $password!='')
    {
        $sql="select id,password from ".$db->prefix."user where name='$user'";
        $result=$db->query($sql);
        if ($result)
        {
            $row = mysql_fetch_row($result);
            if ($password == $row[1]) {
                $this->is_auth=true;
                $this->user=$user;
                $this->userid=$row[0];
                $this->root=$row[2];
                return true;
            }
        }
    }
    return false;
}

function check_session()
{
    $this->check_login($_SESSION['user'], $_SESSION['password']);

    if ($_REQUEST['section']=='account')
    {
        if (!$this->is_auth && $_REQUEST['action']=='login')
        {
            if ($this->check_login($_REQUEST['user'], $_REQUEST['password']))
            {
                $this->set_auth($_REQUEST['user'], $_REQUEST['password']);
            }
        }
        
        if ($_REQUEST['action']=='logout')
        {
            $this->clear_data();
            $this->remove_auth();
            $this->is_logout=true;
        }
    }
    
}

function set_auth($user, $password)
{
    $_SESSION['user']=$user;
    $_SESSION['password']=$password;
}

function remove_auth()
{
    session_destroy();
}

}
?>
