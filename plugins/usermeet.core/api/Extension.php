<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
 * We've never believed in encoding our source code out of paranoia over not 
 * getting paid.  We want you to have the full source code and be able to 
 * make the tweaks your organization requires to get more done -- despite 
 * having less of everything than you might need (time, people, money, 
 * energy).  We shouldn't be your bottleneck.
 * 
 * We've been building our expertise with this project since January 2002.  We 
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to 
 * let us take over your shared e-mail headache is a worthwhile investment.  
 * It will give you a sense of control over your in-box that you probably 
 * haven't had since spammers found you in a game of "E-mail Address 
 * Battleship".  Miss. Miss. You sunk my in-box!
 * 
 * A legitimate license entitles you to support, access to the developer 
 * mailing list, the ability to participate in betas and the warm fuzzy 
 * feeling of feeding a couple obsessed developers who want to help you get 
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
abstract class Extension_UsermeetTool extends DevblocksExtension implements DevblocksHttpRequestHandler {
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
	
	/**
	 * @return Model_CommunitySession
	 */
	protected function getSession() {
		$fingerprint = $this->getFingerprint();
		
		$session_id = md5($fingerprint['ip'] . $fingerprint['local_sessid']);
		$session = DAO_CommunitySession::get($session_id);
		
		return $session;
	}
	
	protected function getFingerprint() {
		$sFingerPrint = DevblocksPlatform::importGPC($_COOKIE['GroupLoginPassport'],'string','');
		$fingerprint = null;
		if(!empty($sFingerPrint)) {
			$fingerprint = unserialize($sFingerPrint);
		}
		return $fingerprint;
	}
	
	// [TODO] Experimental ==========================================
	public function setPortal($code) {
		$this->portal = $code;
	}
	
	public function getPortal() {
		return $this->portal;
	}
	//===============================================================
	
	public function writeResponse(DevblocksHttpResponse $response) {
	}
	
	/**
	 * @param Model_CommunityTool $instance
	 */
	public function configure($instance) {
	}
	
	public function saveConfiguration() {
	}
    
};

abstract class Extension_UsermeetWidget extends DevblocksExtension {
    function __construct($manifest) {
        // [TODO] Refactor to __construct
        parent::DevblocksExtension($manifest);
    }
    
    /*
     */
};

?>