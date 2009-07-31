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

abstract class Extension_AppPreBodyRenderer extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class Extension_AppPostBodyRenderer extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class CerberusPageExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function isVisible() { return true; }
	function render() { }
	
	/**
	 * @return Model_Activity
	 */
	public function getActivity() {
        return new Model_Activity('activity.default');
	}
};

abstract class Extension_ConfigTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_PreferenceTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_ActivityTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_HomeTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_ResearchTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_TicketTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_LogMailToolbarItem extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class Extension_SendMailToolbarItem extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render() { }
};

abstract class Extension_TicketToolbarItem extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render(CerberusTicket $ticket) { }
};

abstract class Extension_MessageToolbarItem extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render(CerberusMessage $message) { }
};

abstract class Extension_ReplyToolbarItem extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function render(CerberusMessage $message) { }
};

abstract class Extension_OrgTab extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function showTab() {}
	function saveTab() {}
};

abstract class Extension_MailFilterCriteria extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function matches(Model_PreParseRule $filter, CerberusParserMessage $message) {}
	
	function renderConfig(Model_PreParseRule $filter=null) {}
	function saveConfig() { return array(); }
};

abstract class Extension_MailFilterAction extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
	
	function run(Model_PreParseRule $filter, CerberusParserMessage $message) {}
	
	function renderConfig(Model_PreParseRule $filter=null) {}
	function saveConfig() { return array(); }
};

abstract class Extension_TaskSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.task.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function getSourceName() {}
	function getSourceInfo($object_id) {}
};

abstract class Extension_NoteSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.notes.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
};

abstract class Extension_CustomFieldSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.fields.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
};

abstract class Extension_RssSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.rss.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function getFeedAsRss($feed) {}
};

abstract class Extension_WorkspaceSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.workspace.source';
	
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
};

abstract class CerberusLoginPageExtension extends DevblocksExtension { //implements DevblocksHttpRequestHandler {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}
	
	/**
	 * draws html form for adding necessary settings (host, port, etc) to be stored in the db
	 */
	function renderConfigForm() {
	}
	
	/**
	 * Receives posted config form, saves to manifest
	 */
	function saveConfiguration() {
//		$field_value = DevblocksPlatform::importGPC($_POST['field_value']);
//		$this->params['field_name'] = $field_value;
	}
	
	/**
	 * draws HTML form of controls needed for login information
	 */
	function renderLoginForm() {
	}
	
	/**
	 * pull auth info out of $_POST, check it, return user_id or false
	 * 
	 * @return boolean whether login succeeded
	 */
	function authenticate($params=array()) {
		return false;
	}
	
	/**
	 * release any resources tied up by the authenticate process, if necessary
	 */
	function signoff() {
	}
	
}

// [TODO] Convert to a controller extension
abstract class CerberusCronPageExtension extends DevblocksExtension {
    const PARAM_ENABLED = 'enabled';
    const PARAM_LOCKED = 'locked';
    const PARAM_DURATION = 'duration';
    const PARAM_TERM = 'term';
    const PARAM_LASTRUN = 'lastrun';
    
	function __construct($manifest) {
		$this->DevblocksExtension($manifest, 1);
	}

	/**
	 * runs scheduled task
	 *
	 */
	function run() {
	    // Overloaded by child
	}
	
	function _run() {
	    $this->run();
	    
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
	    $lastrun = $this->getParam(self::PARAM_LASTRUN, time());

	    $secs = self::getIntervalAsSeconds($duration, $term);
	    $ran_at = time();
	    
	    if(!empty($secs)) {
		    $gap = time() - $lastrun; // how long since we last ran
		    $extra = $gap % $secs; // we waited too long to run by this many secs
		    $ran_at = time() - $extra; // go back in time and lie
	    }
	    
	    $this->setParam(self::PARAM_LASTRUN,$ran_at);
	    $this->setParam(self::PARAM_LOCKED,0);
	}
	
	/**
	 * @param boolean $is_ignoring_wait Ignore the wait time when deciding to run
	 * @return boolean
	 */
	public function isReadyToRun($is_ignoring_wait=false) {
		$locked = $this->getParam(self::PARAM_LOCKED, 0);
		$enabled = $this->getParam(self::PARAM_ENABLED, false);
		$duration = $this->getParam(self::PARAM_DURATION, 5);
		$term = $this->getParam(self::PARAM_TERM, 'm');
		$lastrun = $this->getParam(self::PARAM_LASTRUN, 0);
		
		// If we've been locked too long then unlock
	    if($locked && $locked < (time() - 10 * 60)) {
	        $locked = 0;
	    }

	    // Make sure enough time has elapsed.
	    $checkpoint = ($is_ignoring_wait)
	    	? (0) // if we're ignoring wait times, be ready now
	    	: ($lastrun + self::getIntervalAsSeconds($duration, $term)) // otherwise test
	    	;

	    // Ready?
	    return (!$locked && $enabled && time() >= $checkpoint) ? true : false;
	}
	
	static public function getIntervalAsSeconds($duration, $term) {
	    $seconds = 0;
	    
	    if($term=='d') {
	        $seconds = $duration * 24 * 60 * 60; // x hours * mins * secs
	    } elseif($term=='h') {
	        $seconds = $duration * 60 * 60; // x * mins * secs
	    } else {
	        $seconds = $duration * 60; // x * secs
	    }
	    
	    return $seconds;
	}
	
	public function configure($instance) {}
	
	public function saveConfigurationAction() {}
	
}

?>
