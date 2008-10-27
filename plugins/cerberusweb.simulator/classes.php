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
class ChSimulatorPlugin extends DevblocksPlugin {
	
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class ChSimulatorTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);	
		}
		
		function getTmxFile() {
			return dirname(__FILE__) . '/strings.xml';
		}
	};
endif;

class ChSimulatorConfigTab extends Extension_ConfigTab {
	const ID = 'simulator.config.tab';
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		$flavors = array(
			'hosting' => 'Web Hosting',
			'retail' => 'Retail',
			'edu' => 'Education',
			'gov' => 'Government',
			'npo' => 'Non-profit/Charity',
			'spam' => 'Spam',
		);
		$tpl->assign('flavors', $flavors);
		
		$tpl->display('file:' . $tpl_path . 'config_tab/index.tpl.php');
	}
	
	function generateTicketsAction() {
		require_once(dirname(__FILE__) . '/api/API.class.php');
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = realpath(dirname(__FILE__) . '/templates') . DIRECTORY_SEPARATOR;
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";
		
		@$address = DevblocksPlatform::importGPC($_POST['address'],'string'); 
		@$dataset = DevblocksPlatform::importGPC($_POST['dataset'],'string');
		@$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer',0);

		if(empty($address)) {
			$tpl->assign('error', sprintf("Oops! '%s' is not a valid e-mail address.", htmlspecialchars($address)));
			$tpl->display('file:' . $tpl_path . 'config_tab/output.tpl.php');
			return;
		}
		
		// [JAS]: [TODO] This should probably move to an extension point later
		switch($dataset) {
			default:
			case "retail":
				$dataset = new RetailDataset();
				break;
			case "hosting":
				$dataset = new HostingDataset();
				break;
			case "edu":
				$dataset = new EduDataset();
				break;
			case "gov":
				$dataset = new GovDataset();
				break;
			case "npo":
				$dataset = new NPODataset();
				break;
			case "spam":
				$dataset = new SpamDataset();
				break;
		}
		
		$simulator = CerberusSimulator::getInstance();
		$emails = $simulator->generateEmails($dataset,$how_many);

		foreach($emails as $template) {
		    if(preg_match("/\"(.*?)\" \<(.*?)\>/", $template['sender'], $matches)) {
		        $personal = $matches[1];
		        $from = $matches[1];
		    } // [TODO] error checking
		    
            $message = new CerberusParserMessage();
            $message->headers['from'] = $template['sender'];
            $message->headers['to'] = $address;
            $message->headers['subject'] = $template['subject'];
            $message->headers['message-id'] = CerberusApplication::generateMessageId();
            
            $message->body = sprintf(
				"%s\r\n".
				"\r\n".
				"--\r\n%s\r\n",
				$template['body'],
				$personal
			);
		    
			CerberusParser::parseMessage($message,array('no_autoreply'=>true));
		}
		
		$tpl->assign('output', sprintf("Success!  %d simulated tickets were generated for %s", $how_many, htmlspecialchars($address)));
		
		$tpl->display('file:' . $tpl_path . 'config_tab/output.tpl.php');
	}
	
};


?>
