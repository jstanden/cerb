<?php
class CerberusApplicationUtilsTest extends PHPUnit_Framework_TestCase {
    final function __construct($name = null, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);
    }
    
    public function testGenerateMessageId() {
    	$msg_id = CerberusApplication::generateMessageId();
    	
    	// Test the format of the message-id
    	$actual = 
    		'<' == substr($msg_id,0,1) 
    		&& '>' == substr($msg_id,-1)
    	 	&& false !== strpos($msg_id, '@')
    	 	;
    	$this->assertEquals(true, $actual);
    }
    
    public function testGeneratePassword() {
    	// 8 character password
    	$actual = CerberusApplication::generatePassword(8);
    	$this->assertEquals(8, strlen($actual));
    	
    	// 16 character password
    	$actual = CerberusApplication::generatePassword(16);
    	$this->assertEquals(16, strlen($actual));
    }
    
    
}
