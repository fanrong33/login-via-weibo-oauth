<?php
class CommonAction extends Action {
	
	protected $_user;
	
	public function _initialize(){
		
    	if($_SESSION['is_logined']){
	    	$this->_user = $_SESSION['user'];

	    	$this->assign('_is_logined', $_SESSION['is_logined']);
	    	$this->assign('_user', $_SESSION['user']);
    	}
    	
	}
	

    		
}