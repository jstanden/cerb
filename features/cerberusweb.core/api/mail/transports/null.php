<?php

use Cerb\Email\Crypto\PgpEncrypter;
use Cerb\Email\Crypto\PgpSigner;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class CerbMailTransport_Null extends Extension_MailTransport {
	const ID = 'core.mail.transport.null';
	
	private ?string $_lastErrorMessage = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/null/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) : bool {
		return true;
	}
	
	function send(Model_DevblocksOutboundEmail $email_model, Model_MailTransport $model) : bool {
		try {
			$smtp_message = CerberusMail::getSmtpMessageFromModel($email_model);
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			$this->_lastErrorMessage = "An unexpected error occurred.";
			return false;
		}
		
		if(($outgoing_message_id = $email_model->getProperty('outgoing_message_id'))) {
			$smtp_message->getHeaders()->addIdHeader('Message-ID', $outgoing_message_id);
			unset($outgoing_message_id);
		}
		
		// X-Mailer
		$smtp_message->getHeaders()->addTextHeader('X-Mailer', 'Cerb ' . APP_VERSION . ' (Build ' . APP_BUILD . ')');
		
		$to = $smtp_message->getTo();
		$from = $smtp_message->getFrom();

		if(!$to) {
			$this->_lastErrorMessage = "At least one 'To:' recipient address is required.";
			return false;
		}
		
		if(!$from) {
			$this->_lastErrorMessage = "A 'From:' sender address is required.";
			return false;
		}
		
		if(!($mailer = $this->_getMailer()))
			return false;
		
		try {
			if($email_model->getProperty('gpg_sign')) {
				$pgp_signer = new PgpSigner();
				$smtp_message = $pgp_signer->sign($smtp_message, $email_model);
			}
			
			if($email_model->getProperty('gpg_encrypt')) {
				$pgp_encrypter = new PgpEncrypter();
				$smtp_message = $pgp_encrypter->encrypt($smtp_message, $email_model);
			}
			
			$mailer->send($smtp_message);
			
			if(DEVELOPMENT_MODE) {
				file_put_contents(APP_TEMP_PATH . '/email.msg', $smtp_message->toString());
			}
			
			$outgoing_headers = $smtp_message->getPreparedHeaders()->toString();
			
			if($bcc_header = $smtp_message->getHeaders()->get('Bcc'))
				$outgoing_headers .= $bcc_header->toString() . "\r\n";
			
			$email_model->setResult('outgoing_email_headers', $outgoing_headers);
			
			return true;
			
		} catch(TransportException | RfcComplianceException | Exception_DevblocksEmailDeliveryError $e) {
			DevblocksPlatform::logException($e);
			$this->_lastErrorMessage = $e->getMessage();
			return false;
			
		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
			$this->_lastErrorMessage = "There was an error while trying to send the message.";
			return false;
		}
	}
	
	function getLastError() : ?string {
		return $this->_lastErrorMessage;
	}
	
	private function _getMailer() : Mailer {
		static $mailer = null;
		
		if(is_null($mailer)) {
			$null = new NullTransport();
			$mailer = new Mailer($null);
		}
		
		return $mailer;
	}
}