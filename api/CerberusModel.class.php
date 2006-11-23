<?php

class CerberusDashboardView {
	public $id = 0;
	public $name = "";
	public $dashboard_id = 0;
	public $params = array();
	
	public $renderPage = 0;
	public $renderLimit = 10;
	public $renderSortBy = 't.subject';
	public $renderSortAsc = 1;
	
	function getTickets() {
		$tickets = CerberusTicketDAO::searchTickets(
			array(),
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
//		$tpl->assign('tickets', $tickets[0]);
//		$tpl->assign('total', $tickets[1]);
		return $tickets;	
	}
};

?>