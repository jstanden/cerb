<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */

class PageMenuItem_SetupSimulator extends Extension_PageMenuItem {
	const ID = 'simulator.setup.menu.mail.simulator';
	
	public function render() {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->display('devblocks:cerberusweb.simulator::setup/menu_simulator.tpl');
	}
};

class PageSection_SetupSimulator extends Extension_PageSection {
	const ID = 'simulator.setup.section.simulator';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();
		
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
	
	function generateTasksJsonAction() {
		try {
			@$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer',0);
			
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$title = "{{action}} {{whos}} {{thing}} {{because}}";
			
			$variables = array(
				'action' => array(
					'Erase',
					'RMA',
					'Locate',
					'Destroy',
					'Replace',
					'Update',
					'Audit',
					'Fix',
					'Repair',
					'Find',
					'Migrate',
				),
				'whos' => array(
					"customer's",
					"client's",
					"CEO's",
					"janitor's",
					"I.T.'s",
					"the new sales guy's",
					"HQ's",
				),
				'thing' => array(
					'website',
					'server',
					'hard drive',
					'Windows machine',
					'Blackberry',
					'iPhone',
					'laptop',
					'login',
					'iPad',
					'Linux box',
					'iMac',
				),
				'because' => array(
					'because the shareholders are on to us',
					'since I.T. funding was scaled back',
					"since all they really asked for was coffee",
					"because they can't figure it out",
					'due to cutbacks',
					'since the new one is provisioned',
					'since we only purchased it because of flashy marketing anyway',
					"because it won't fit in the shredder",
					'because they asked for more disk space',
					"because it's " . strftime("%A"),
					"because it is " . strftime("%B"),
				),
			);

			for($x=0;$x<$how_many;$x++) {
				$params = array();
				foreach($variables as $k=>$data) {
					shuffle($data);
					$params[$k] = $data[array_rand($data)];
				}
				
				$task_title = $tpl_builder->build(
					$title,
					$params
				);

				$due_increment = mt_rand(1,24);
				$due_unit = (mt_rand(0,1)) ? 'hours' : 'days';
				$due_timestamp = strtotime('+'.$due_increment.' '.$due_unit);
				
				// [TODO] Randomly context link to orgs
				
				$task_id = DAO_Task::create(array(
					DAO_Task::TITLE => $task_title,
					DAO_Task::UPDATED_DATE => time(),
					DAO_Task::DUE_DATE => $due_timestamp,
				));
			}
			
			echo json_encode(array('status'=>true, 'message'=>sprintf("Success! %d simulated tasks were generated.", $how_many)));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
		
	}
	
	function generateOrgsJsonAction() {
		try {
			@$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer',0);
			
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$org_name_tpl = "{{intro}}{{consonant}}{{vowel}} {{industry}}{{suffix}}";
			$website_tpl = "http://{{intro}}{{consonant}}{{vowel}}{{industry}}.cerberusdemo.com/";
			
			$variables = array(
				'intro' => array(
					'ba',
					'bi',
					'bra',
					'do',
					'en',
					'er',
					'fa',
					'fi',
					'ge',
					'go',
					'gri',
					'hi',
					'hu',
					'ire',
					'je',
					'kra',
					'ku',
					'li',
					'lo',
					'lo',
					'ne',
					'ri',
					'sta',
					'sti',
					'twi',
					'vi',
					'via',
					'wa',
					'we',
					'ya',
					'ze',
					'zo',
					'zu',
				),
				'vowel' => array(
					'a',
					'ard',
					'e',
					'ee',
					'er',
					'i',
					'ie',
					'ii',
					'ilio',
					'ilo',
					'ily',
					'int',
					'io',
					'ious',
					'ist',
					'on',
					'oo',
					'ou',
					'ri',
					'u',
					'us',
					'ux',
					'y',
					'',
					'',
					'',
				),
				'consonant' => array(
					'b',
					'bb',
					'br',
					'ch',
					'cr',
					'fi',
					'fu',
					'gh',
					'gr',
					'l',
					'ld',
					'nd',
					'nt',
					'rd',
					'rst',
					'st',
					'z',
					'',
					'',
					'',
				),
				'industry' => array(
					'Aerospace',
					'Airways',
					'Collective',
					'Defense',
					'Education',
					'Events',
					'Finance',
					'Games',
					'Institute',
					'Loans',
					'Logistics',
					'Media',
					'Mobile',
					'Music',
					'Online',
					'Pharmaceuticals',
					'Republic',
					'Software',
					'United',
					'Ventures',
					'',
				),
				'suffix' => array(
					' Corp.',
					' GmbH',
					' Incorporated',
					' LLC',
					' Ltd.',
					', Inc.',
					'',
					'',
				),
			);
		
			$org_name_tpl = str_replace(
				array(
					'  ',
					' ,',
				),
				array(
					' ',
					',',
				),
				trim($org_name_tpl)
			);
			
			$countries = array(
				'Australia',
				'Belgium',
				'Brazil',
				'Canada',
				'France',
				'Germany',
				'India',
				'Italy',
				'Mexico',
				'Netherlands',
				'Norway',
				'Poland',
				'Russia',
				'Singapore',
				'South Africa',
				'Spain',
				'Sweden',
				'United Kingdom',
				'United States',
			);
			
			for($x=0;$x<$how_many;$x++) {
				$params = array();
				foreach($variables as $k=>$data) {
					shuffle($data);
					$params[$k] = $data[array_rand($data)];
				}
				
				$org_name = $tpl_builder->build(
					$org_name_tpl,
					$params
				);
				
				$org_name = mb_ucfirst($org_name);
				
				$website = $tpl_builder->build(
					$website_tpl,
					$params
				);
				
				$website = DevblocksPlatform::strLower($website);
				
				shuffle($countries);
				
				$country = $countries[array_rand($countries)];

				// [TODO] Randomly context link to orgs
				
				$org_id = DAO_ContactOrg::create(array(
					DAO_ContactOrg::NAME => $org_name,
					DAO_ContactOrg::CREATED => time(),
					DAO_ContactOrg::COUNTRY => $country,
					DAO_ContactOrg::WEBSITE => $website,
				));
			}
			
			echo json_encode(array('status'=>true, 'message'=>sprintf("Success! %d simulated orgs were generated.", $how_many)));
			return;
			
		} catch(Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
		
	}
	
};
