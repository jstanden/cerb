<?php
class ChSpamAnalysisTicketTab extends Extension_TicketTab {
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)).'/templates/';
		$tpl->assign('path', $tpl_path);

		$ticket = DAO_Ticket::get($ticket_id);
		$tpl->assign('ticket_id', $ticket_id);
		$tpl->assign('ticket', $ticket);
		
		// Receate the original spam decision
		$words = DevblocksPlatform::parseCsvString($ticket->interesting_words);
		$words = DAO_Bayes::lookupWordIds($words);

		// Calculate word probabilities
		foreach($words as $idx => $word) { /* @var $word Model_BayesWord */
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