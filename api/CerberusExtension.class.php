<?php

abstract class CerberusModuleExtension extends UserMeetExtension {
	function CerberusModuleExtension($manifest) {
		$this->UserMeetExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	function getLink() {
		return "?c=".$this->manifest->id."&a=click";
	}
	function click() { 
//		echo "You clicked: " . __CLASS__ . "->" . __FUNCTION__ . "!<br>";
		CerberusApplication::setActiveModule($this->manifest->id);
	}
};

abstract class CerberusDisplayModuleExtension extends UserMeetExtension {
	function CerberusDisplayModuleExtension($manifest) {
		$this->UserMeetExtension($manifest,1);
	}
	
	/**
	 * Enter description here...
	 */
	function render($ticket) {}
}

?>