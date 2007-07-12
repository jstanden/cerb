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
class ChFaqApp extends Extension_UsermeetTool {
    function __construct($manifest) {
        parent::__construct($manifest);
        
        $filepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        
        DevblocksPlatform::registerClasses($filepath.'api/DAO.php',array(
            'DAO_Faq',
            'SearchFields_Faq'
        ));
        DevblocksPlatform::registerClasses($filepath.'api/Model.php',array(
            'Model_Faq'
        ));
    }
    
	public function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$stack = $response->path;
		
		// Usermeet Session
		@$fingerprint = unserialize($_COOKIE['GroupLoginPassport']);
//		if(empty($fingerprint)) die("..."); // [TODO] Fix
        $tpl->assign('fingerprint', $fingerprint);

		switch(array_shift($stack)) {
		    case 'search':
		        @$q = DevblocksPlatform::importGPC($_POST['q']);
		        
		        $criteria = array();
		        
		        if(!empty($q))
                    $criteria[] = new DevblocksSearchCriteria(SearchFields_Faq::QUESTION,DevblocksSearchCriteria::OPER_LIKE,'%'.$q.'%');
		        
		        list($faqs, $faqs_count) = DAO_Faq::search(
		            $criteria,
				    10,
				    0
				);
				$tpl->assign('q', $q);
				$tpl->assign('results', $faqs);
				$tpl->assign('results_count', $faqs_count);
				
				$tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/faq/index.tpl.php');
		        break;
		        
		    default:
	            $faqs = DAO_Faq::getList();
	            $tpl->assign('faqs', $faqs);
		        $tpl->display('file:' . dirname(__FILE__) . '/templates/usermeet/faq/index.tpl.php');
		        break;
		}
	}
	
	function doAskAction() {
	    @$q = DevblocksPlatform::importGPC($_POST['q']);
	    
	    $fields = array(
	        DAO_Faq::QUESTION => $q,
	    );
	    $id = DAO_Faq::create($fields);

	    // Fire off a FAQ question event
	    $faq = DAO_Faq::get($id);
	    $eventMgr = DevblocksPlatform::getEventService();
	    $eventMgr->trigger(
	        new Model_DevblocksEvent(
	            'faq.question_asked',
                array(
                    'id' => $faq->id,
                    'question' => $faq->question,
                )
            )
	    );
	    
//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse());
	}

	/**
	 * @param $instance Model_CommunityTool 
	 */
    public function configure($instance) {
        $tpl = DevblocksPlatform::getTemplateService();
        $tpl_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        
        
        $tpl->display("file:${tpl_path}usermeet/faq/config.tpl.php");
    }
    
    public function saveConfigurationAction() {
        
    }
	
};
?>