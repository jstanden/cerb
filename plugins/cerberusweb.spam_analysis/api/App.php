<?php
// Classes
$path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;

//DevblocksPlatform::registerClasses($path. 'api/App.php', array(
//    'C4_CrmOpportunityView'
//));

class ChSpamAnalysisPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

class ChSpamAnalysisTranslations extends DevblocksTranslationsExtension {
	function __construct($manifest) {
		parent::__construct($manifest);	
	}
	
	function getTmxFile() {
		return dirname(dirname(__FILE__)) . '/strings.xml';
	}
};

class ChSpamAnalysisTicketTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);
		$tpl->cache_lifetime = "0";

		$ticket = DAO_Ticket::getTicket($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('ticket', $ticket);
		
		// Receate the original spam decision
		$words = DevblocksPlatform::parseCsvString($ticket->interesting_words);
		$words = DAO_Bayes::lookupWordIds($words);

		// Calculate word probabilities
		foreach($words as $idx => $word) { /* @var $word CerberusBayesWord */
			$word->probability = CerberusBayes::calculateWordProbability($word);
		}
		$tpl->assign('words', $words);
		
		// Determine what the spam probability would be if the decision was made right now
		$analysis = CerberusBayes::calculateTicketSpamProbability($ticket_id, true);
		$tpl->assign('analysis', $analysis);
		
		$tpl->display('file:' . $tpl_path . 'ticket_tab/index.tpl');
	}
	
	function saveTab() {
	}
};

?>