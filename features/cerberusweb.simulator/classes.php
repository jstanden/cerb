<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
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

class PageMenuItem_SetupSimulator extends Extension_PageMenuItem {
	const ID = 'simulator.setup.menu.mail.simulator';
	
	public function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.simulator::setup/menu_mail_simulator.tpl');
	}
};

class PageSection_SetupSimulator extends Extension_PageSection {
	const ID = 'simulator.setup.section.simulator';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$flavors = array(
			'hosting' => 'Web Hosting',
			'retail' => 'Retail',
			'edu' => 'Education',
			'gov' => 'Government',
			'npo' => 'Non-profit/Charity',
			'spam' => 'Spam',
		);
		$tpl->assign('flavors', $flavors);
		
		$tpl->display('devblocks:cerberusweb.simulator::setup/section.tpl');
	}
	
	function generateTicketsJsonAction() {
		require_once(dirname(__FILE__) . '/api/API.class.php');
		
		try {
			@$address = DevblocksPlatform::importGPC($_POST['address'],'string'); 
			@$dataset = DevblocksPlatform::importGPC($_POST['dataset'],'string');
			@$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer',0);
			
			if(empty($address))
				throw new Exception(sprintf("Oops! '%s' is not a valid e-mail address.", $address));

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
			
			// Create the messages using the dataset
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
			
			echo json_encode(array('status'=>true, 'message'=>sprintf("Success!  %d simulated tickets were generated for %s", $how_many, htmlspecialchars($address))));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
	}
};
