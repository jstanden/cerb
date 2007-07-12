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
class ChFaqPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);

		$path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		
		DevblocksPlatform::registerClasses($path. 'api/DAO.php', array(
		    'DAO_Faq'
		));
		
		DevblocksPlatform::registerClasses($path. 'api/Model.php', array(
		    'Model_Faq'
		));
	}
		
	function isVisible() {
		// check login
		$session = DevblocksPlatform::getSessionService();
		$visit = $session->getVisit();
		
		if(empty($visit)) {
			return false;
		} else {
			return true;
		}
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;

		list($faqs, $faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,0)
		    ),
		    100,
		    0
		);
		
		$tpl->assign('faqs', $faqs);

		list($popular_faqs, $popular_faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,1)
		    ),
		    100,
		    0
		);

		$tpl->assign('popular_faqs', $popular_faqs);
				
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/index.tpl.php');
	}
	
	function renderAction() {
	    self::render();
	}
	
	// Ajax
	function answerAction() {
	    @$id = intval(DevblocksPlatform::importGPC($_REQUEST['id'],'integer'));
	    @$question = DevblocksPlatform::importGPC($_REQUEST['question'],'string');
	    @$answer = DevblocksPlatform::importGPC($_REQUEST['answer'],'string');
	    @$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer');
	    
	    $worker = CerberusApplication::getActiveWorker();
	    
	    if(empty($question) || empty($worker)) {
	        echo ' ';
	        return;
	    }

	    if($delete) {
	        DAO_Faq::delete(array($id));
	        
	    } else {
		    $fields = array();
		    
		    if(!empty($question))
		        $fields[DAO_Faq::QUESTION] = $question;
		    
		    if(!empty($answer)) {
		        $fields[DAO_Faq::ANSWER] = $answer;
	            $fields[DAO_Faq::IS_ANSWERED] = 1;
	            $fields[DAO_Faq::ANSWERED_BY] = $worker->id;
		    }
	
		    if(empty($id)) {
		        $id = DAO_Faq::create($fields);
		    } else {
	            DAO_Faq::update($id, $fields);
		    }
	    }

//	    DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('faq')));
        echo ' ';
	}
	
	// Ajax
	function showFaqPanelAction() {
	    @$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
	    
	    include_once(DEVBLOCKS_PATH . 'libs/markdown/markdown.php');
		$tpl->register_modifier('markdown','smarty_modifier_markdown');
		
		$faq = DAO_Faq::get($id);
		$tpl->assign('faq', $faq);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/faq_panel.tpl.php');
	}
	
	// Ajax
	function showFaqSearchPanelAction() {
	    @$q = DevblocksPlatform::importGPC($_REQUEST['q']);
	    
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');

		if(!empty($q)) {
		    // [TODO] Do search
	        list($results, $results_count) = DAO_Faq::search(
	            array(
	                new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,1)
	            ),
	            25,
	            0
	        );
	        $tpl->assign('results', $results);
	        $tpl->assign('results_count', $results_count);

	        $tpl->assign('query', $q);
		}
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/faq/faq_search_panel.tpl.php');
	}
	
	function showFaqAnswerAction() {
	    @$id = intval(DevblocksPlatform::importGPC($_REQUEST['id']));
	    
	    $faq = DAO_Faq::get($id);
	    
	    if(!empty($faq)) {
	   		$tpl = DevblocksPlatform::getTemplateService();
			$tpl->cache_lifetime = "0";
			$tpl->assign('path', dirname(__FILE__) . '/templates/');
	        
		    include_once(DEVBLOCKS_PATH . 'libs/markdown/markdown.php');
			$tpl->register_modifier('markdown','smarty_modifier_markdown');
			
			$tpl->assign('faq', $faq);
	        
	        $tpl->display('file:' . dirname(__FILE__) . '/templates/faq/faq_answer.tpl.php');
	    } else
	        echo ' ';
	        
	}
	
};

class ChDisplayFaq extends CerberusDisplayPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function render($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/ticket_fnr.tpl.php');
	}
	
	function renderBody($ticket) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		
		list($fnr_faqs, $fnr_faqs_count) = DAO_Faq::search(
		    array(
		        new DevblocksSearchCriteria(SearchFields_Faq::IS_ANSWERED,DevblocksSearchCriteria::OPER_EQ,1)
		    ),
		    5,
		    0
		);
		$tpl->assign('fnr_faqs', $fnr_faqs);
				
		$tpl->display('file:' . dirname(__FILE__) . '/templates/display/faq/index.tpl.php');
	}
};

?>