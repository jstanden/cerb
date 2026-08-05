<?php
namespace Cerb\Email;

use CerberusContexts;
use CerberusMail;
use DAO_Message;
use DAO_Ticket;
use Extension_DevblocksContext;

// Shared simulator priming for the mail.filter / mail.route triggers: both build the identical runtime scope
// (see CerberusParser::_buildMailFilterInitialState / _parseMessageRoutingAutomations), so both prime it the same
// way — pick one existing message and expand it into the full scope. Mirrors the routing-rule builder's chooser.
trait MailScopeSimulation {
	function getSimulationInputs() : array {
		$ext = Extension_DevblocksContext::getByAlias('message', true);

		$d = [
			'key' => 'example_message',
			'label' => 'Example message to simulate against',
			'component' => 'chooser',
			'emit' => 'record',
			'record_type' => 'message',
			'context' => ($ext ? $ext->id : ''),
		];

		// Pre-fill a sample so "Run" works out of the box.
		if(($dao = $ext ? $ext->getDaoClass() : null) && method_exists($dao, 'random'))
			$d['default'] = $dao::random();

		return [$d];
	}

	function getSimulationState(array $answers, &$error = null) : array {
		$id = intval($answers['example_message'] ?? 0);

		if($id < 1 || false == ($message = DAO_Message::get($id))) {
			$error = 'Select an example message to simulate against.';
			return [];
		}

		$headers = $message->getHeaders();   // parsed, lowercase keys

		$subject = $headers['subject'] ?? '';
		if(is_array($subject))
			$subject = (string) array_shift($subject);

		// Subject falls back to the parent ticket's subject when the message carries no Subject header.
		if($subject === '' && $message->ticket_id && ($ticket = DAO_Ticket::get($message->ticket_id)))
			$subject = $ticket->subject;

		// Recipient emails across the To/Cc/Envelope-To/Delivered-To headers (matches Model::getRecipients()).
		$recipients = [];
		foreach(['to', 'cc', 'envelope-to', 'x-envelope-to', 'delivered-to'] as $header) {
			if(empty($headers[$header]))
				continue;
			foreach((array) CerberusMail::parseRfcAddresses($headers[$header]) as $addy) {
				if(!empty($addy['mailbox']) && !empty($addy['host']))
					$recipients[] = $addy['mailbox'] . '@' . $addy['host'];
			}
		}

		$html_body = $message->getContentAsHtml();

		return [
			'email_sender__context' => CerberusContexts::CONTEXT_ADDRESS,
			'email_sender_id' => intval($message->address_id),
			'email_subject' => strval($subject),
			'email_headers' => $headers,
			'email_body' => $message->getContent(),
			'email_body_html' => is_string($html_body) ? $html_body : '',
			'email_recipients' => $recipients,
			'parent_ticket__context' => CerberusContexts::CONTEXT_TICKET,
			'parent_ticket_id' => intval($message->ticket_id),
		];
	}
}
