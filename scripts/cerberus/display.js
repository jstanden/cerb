var cDisplayTicketAjax = function(ticket_id) {
	this.ticket_id = ticket_id;

	this.reloadTicketTasks = function(o) {
		genericAjaxGet('core.display.module.tasks_body','c=display&a=reloadTasks&ticket_id=' + displayAjax.ticket_id);
	}
	
}