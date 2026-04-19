<?php

use Cerb\Email\Crypto\PgpEncrypter;
use Cerb\Email\Crypto\PgpSigner;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\PlainAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class CerbMailTransport_Smtp extends Extension_MailTransport {
	const ID = 'core.mail.transport.smtp';
	
	private ?string $_lastErrorMessage = null;
	
	function renderConfig(Model_MailTransport $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('model', $model);
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:cerberusweb.core::internal/mail_transport/smtp/config.tpl');
	}
	
	function testConfig(array $params, &$error=null) {
		$host = $params['host'] ?? null;
		$port = $params['port'] ?? null;
		$encryption = $params['encryption'] ?? null;
		$auth_enabled = $params['auth_enabled'] ?? null;
		$auth_user = $params['auth_user'] ?? null;
		$auth_pass = $params['auth_pass'] ?? null;
		$connected_account_id = $params['connected_account_id'] ?? 0;
		
		if(empty($host)) {
			$error = 'The SMTP "host" parameter is required.';
			return false;
		}
		
		if(empty($port)) {
			$error = 'The SMTP "port" parameter is required.';
			return false;
		}
		
		// Try connecting
		
		$options = [
			'host' => $host,
			'port' => $port,
			'enc' => $encryption,
			'timeout' => 10,
			'ssl_disable_validation' => $params['ssl_disable_validation'] ?? 0,
		];
		
		if($auth_enabled) {
			$options['auth_user'] = $auth_user;
			$options['auth_pass'] = $auth_pass;
		}
		
		if($connected_account_id && $auth_enabled) {
			$options['connected_account_id'] = $connected_account_id;
		}
		
		try {
			if(null == ($smtp = $this->_getTransport($options)))
				throw new Exception("Failed to start mailer transport");
			
			$smtp->start();
			$smtp->stop();
			return true;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			return false;
		}
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
		
		$options = [
			'host' => $model->params['host'] ?? null,
			'port' => $model->params['port'] ?? null,
			'auth_user' => $model->params['auth_user'] ?? null,
			'auth_pass' => $model->params['auth_pass'] ?? null,
			'enc' => $model->params['encryption'] ?? null,
			'max_sends' => $model->params['max_sends'] ?? null,
			'timeout' => $model->params['timeout'] ?? null,
			'connected_account_id' => $model->params['connected_account_id'] ?? null,
			'ssl_disable_validation' => $model->params['ssl_disable_validation'] ?? 0,
		];
		
		// Error messages are inherited
		if(!($mailer = $this->_getMailer($options)))
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
	
	private function _getMailer(array $options) : ?Mailer {
		$smtp = $this->_getTransport($options);
		return new Mailer($smtp);
	}
	
	private function _getTransport(array $options) : ? EsmtpTransport {
		static $connections = [];
		
		// Options
		$smtp_host = $options['host'] ?? '127.0.0.1';
		$smtp_port = $options['port'] ?? '25';
		$smtp_user = $options['auth_user'] ?? null;
		$smtp_pass = $options['auth_pass'] ?? null;
		$smtp_enc = $options['enc'] ?? 'None';
		$smtp_max_sends = intval($options['max_sends'] ?? 20);
		$smtp_timeout = intval($options['timeout']) ?? 30;
		$smtp_connected_account_id = intval($options['connected_account_id'] ?? 0);
		$ssl_disable_validation = $options['ssl_disable_validation'] ?? 0;
		
		/*
		 * [JAS]: We'll cache connection info hashed by params and hold a persistent
		 * connection for the request cycle.  If we ask for the same params again
		 * we'll get the existing connection if it exists.
		 */

		$hash = sha1(json_encode([
			$smtp_host,
			$smtp_user,
			$smtp_pass,
			$smtp_port,
			$smtp_enc,
			$smtp_max_sends,
			$smtp_timeout,
			$smtp_connected_account_id
		]));
		
		if(!isset($connections[$hash])) {
			$smtp = new EsmtpTransport($smtp_host, $smtp_port, $smtp_enc == 'SSL');
			
			$smtp->setLocalDomain(DevblocksPlatform::getHostname());
			
			// Optionally disable SSL validation
			if($ssl_disable_validation) {
				if(($stream = $smtp->getStream())) { /* @var $stream SocketStream */
					$stream_options = $stream->getStreamOptions();
					$stream_options['ssl']['allow_self_signed'] = true;
					$stream_options['ssl']['verify_peer'] = false;
					$stream_options['ssl']['verify_peer_name'] = false;
					$stream->setStreamOptions($stream_options);
				}
			}
			
			$smtp->setAuthenticators([]);

			// Is XOAUTH2 enabled?
			if($smtp_user && $smtp_connected_account_id) {
				$connected_account = DAO_ConnectedAccount::get($smtp_connected_account_id);
				
				if(!($service_extension = $connected_account->getServiceExtension())) {
					$this->_lastErrorMessage = "Failed to load the connected service extension";
					return null;
				}
				
				if(!($service_extension instanceof ServiceProvider_OAuth2)) {
					$this->_lastErrorMessage = "The connected account is not an OAuth2 provider";
					return null;
				}
				
				/** @var $service_extension ServiceProvider_OAuth2 */
				if(!($access_token = $service_extension->getAccessToken($connected_account))) {
					$this->_lastErrorMessage = "Failed to load the access token";
					return null;
				}
				
				$smtp->addAuthenticator(new XOAuth2Authenticator());
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($access_token->getToken());
				
			} else if($smtp_user) {
				$smtp->setUsername($smtp_user);
				$smtp->setPassword($smtp_pass);
			}
			
			// Always try PLAIN last if XOAUTH is enabled
			$smtp->addAuthenticator(new LoginAuthenticator());
			$smtp->addAuthenticator(new PlainAuthenticator());
			
			$smtp->setRestartThreshold($smtp_max_sends, 1);
			
			@ini_set('default_socket_timeout', $smtp_timeout);
			
			$connections[$hash] = $smtp;
		}
		
		if($connections[$hash])
			return $connections[$hash];
		
		return null;
	}
}
