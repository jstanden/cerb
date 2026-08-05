<?php
namespace Cerb\Extensions\AutomationTemplate;

use Cerb\Extensions\Extension_AutomationTemplate;

// "Ticket Changed" — a static `record.changed` starter for tickets that shows how to compare `record_*` (new)
// vs `was_record_*` (before) fields to react to a transition. Targets the record.changed trigger.
class RecordChangedTicket extends Extension_AutomationTemplate {
	const ID = 'cerb.automation.template.records.ticket_changed';

	function build(array $answers) : array {
		$seed = self::_loadAsset('record-changed-ticket.kata');
		$seed['extension_id'] = $this->getTriggerId();

		return $seed;
	}
}
