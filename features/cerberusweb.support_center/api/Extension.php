<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2010, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 * 
 * Sure, it would be so easy to just cheat and edit this file to use the 
 * software without paying for it.  But we trust you anyway.  In fact, we're 
 * writing this software for you! 
 * 
 * Quality software backed by a dedicated team takes money to develop.  We 
 * don't want to be out of the office bagging groceries when you call up 
 * needing a helping hand.  We'd rather spend our free time coding your 
 * feature requests than mowing the neighbors' lawns for rent money. 
 * 
 * We've never believed in hiding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your inbox that you probably 
 * haven't had since spammers found you in a game of 'E-mail Battleship'. 
 * Miss. Miss. You sunk my inbox!
 * 
 * A legitimate license entitles you to support from the developers,  
 * and the warm fuzzy feeling of feeding a couple of obsessed developers 
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */

abstract class Extension_UmScRssController extends DevblocksExtension {
	function handleRequest(DevblocksHttpRequest $request) {
		//$path = $request->path;
	}
};

abstract class Extension_UmScController extends DevblocksExtension implements DevblocksHttpRequestHandler {
	private $portal = '';
	
    function __construct($manifest) {
        // [TODO] Refactor to __construct
        parent::DevblocksExtension($manifest);
    }
    
    /*
     * Site Key
     * Site Name
     * Site URL
     */
    
	/**
	 * @param DevblocksHttpRequest
	 * @return DevblocksHttpResponse
	 */
	public function handleRequest(DevblocksHttpRequest $request) {
	    $path = $request->path;
		@$a = DevblocksPlatform::importGPC($_REQUEST['a'],'string');

		if(empty($a)) {
			@array_shift($path); // controller
    	    @$action = array_shift($path) . 'Action';
		} else {
	    	@$action = $a . 'Action';
		}

	    switch($action) {
	        case NULL:
	            // [TODO] Index/page render
	            break;
//	            
	        default:
			    // Default action, call arg as a method suffixed with Action
				if(method_exists($this,$action)) {
					call_user_func(array(&$this, $action)); // [TODO] Pass HttpRequest as arg?
				}
	            break;
	    }
	}
	
	public function writeResponse(DevblocksHttpResponse $response) {
		/* Expect Overload */
	}
	
	public function renderSidebar(DevblocksHttpResponse $response) {
		/* Expect Overload */
		return;
	}
	
	public function isVisible() {
		/* Expect Overload */
		return true;
	}
	
	public function configure() {
		// [TODO] Translate
		echo "This module has no configuration options.<br><br>";
	}
	
	public function saveConfiguration() {
		/* Expect Overload */
	}
    
};

abstract class Extension_ScLoginAuthenticator extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	/**
	 * draws html form for adding necessary settings (host, port, etc) to be stored in the db
	 */
//	function renderConfigForm() {
//	}
	
	/**
	 * Receives posted config form, saves to manifest
	 */
//	function saveConfiguration() {
//		$field_value = DevblocksPlatform::importGPC($_POST['field_value']);
//		$this->params['field_name'] = $field_value;
//	}
	
	abstract function writeResponse(DevblocksHttpResponse $response);
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
		$umsession = UmPortalHelper::getSession();
		$umsession->logout();
	}
};

abstract class Extension_SupportCenterMessageBadge extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest);
	}
	
	function render(Model_Message $message) {}
};
