<?php

class ChSimulatorPage extends CerberusPageExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
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
	
	function getActivity() {
	    return new Model_Activity('activity.simulator');
	}
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', dirname(__FILE__) . '/templates/');
		
		$response = DevblocksPlatform::getHttpResponse();
		$stack = $response->path;
		
		$flavors = array(
			'hosting' => 'Web Hosting',
			'retail' => 'Retail',
			'edu' => 'Education',
			'gov' => 'Government',
			'npo' => 'Non-profit/Charity',
			'spam' => 'Spam',
		);
		$tpl->assign('flavors', $flavors);
		
		$how_many_opts = array(
			5,
			25,
			50,
			100
		);
		$tpl->assign('how_many_opts', $how_many_opts);
		
		$tpl->display('file:' . dirname(__FILE__) . '/templates/index.tpl.php');
	}
	
	function generateTicketsAction() {
		require_once(dirname(__FILE__) . '/api/API.class.php');
		
		@$address = DevblocksPlatform::importGPC($_POST['address'],'string'); 
		@$dataset = DevblocksPlatform::importGPC($_POST['dataset'],'string');
		@$how_many = DevblocksPlatform::importGPC($_POST['how_many'],'integer');
		
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
            
            $message->body = sprintf(
				"%s\r\n".
				"\r\n".
				"--\r\n%s\r\n",
				$template['body'],
				$personal
			);
		    
			CerberusParser::parseMessage($message);
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('simulator')));
	}
	
};

?>
