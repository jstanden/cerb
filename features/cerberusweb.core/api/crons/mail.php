<?php
/*
 * PARAMS (overloads):
 * mailbox_max=n (max messages to download at once)
 *
 */
class MailboxCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		
		$logger->info("[Mailboxes] Started Mailbox Checker job");
		
		if (!extension_loaded("imap")) {
			$logger->err("[Mailboxes] The 'IMAP' extension is not loaded. Aborting!");
			return false;
		}
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Mailboxes] The 'mailparse' extension is not loaded. Aborting!");
			return false;
		}
		
		@set_time_limit(600); // 10m

		if(false == ($accounts = DAO_Mailbox::getAll())) {
			$logger->err("[Mailboxes] There are no mailboxes to check. Aborting!");
			return false;
		}
		
		// Sort by the least recently checked mailbox
		DevblocksPlatform::sortObjects($accounts, 'checked_at');
		
		$timeout = ini_get('max_execution_time');
		
		// Allow runtime overloads (by host, etc.)
		@$opt_max_messages = DevblocksPlatform::importGPC($_REQUEST['max_messages'],'integer');
		@$opt_max_mailboxes = DevblocksPlatform::importGPC($_REQUEST['max_mailboxes'],'integer');
		
		$max_downloads = !empty($opt_max_messages) ? $opt_max_messages : $this->getParam('max_messages', (($timeout) ? 20 : 50));
		
		// [JAS]: Make sure our output directory is writeable
		if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
			$logger->error("[Mailboxes] The mail storage directory is not writeable.  Skipping mailbox download.");
			return;
		}

		$runtime = microtime(true);
		$mailboxes_checked = 0;
		
		if(is_array($accounts))
		foreach ($accounts as $account) { /* @var $account Model_Mailbox */
			if(!$account->enabled)
				continue;
			
			if($account->delay_until > time()) {
				$logger->info(sprintf("[Mailboxes] Delaying failing mailbox '%s' check for %d more seconds (%s)", $account->name, $account->delay_until - time(), date("h:i a", $account->delay_until)));
				continue;
			}
			
			if($opt_max_mailboxes && $mailboxes_checked >= $opt_max_mailboxes) {
				$logger->info(sprintf("[Mailboxes] We're limited to checking %d mailboxes per invocation. Stopping early.", $opt_max_mailboxes));
				break;
			}
			
			// Per-account IMAP timeouts
			$imap_timeout = !empty($account->timeout_secs) ? $account->timeout_secs : 30;
			
			imap_timeout(IMAP_OPENTIMEOUT, $imap_timeout);
			imap_timeout(IMAP_READTIMEOUT, $imap_timeout);
			imap_timeout(IMAP_CLOSETIMEOUT, $imap_timeout);
			
			$imap_timeout_read_ms = imap_timeout(IMAP_READTIMEOUT) * 1000; // ms
			$imap_options = array();
			
			// [TODO] Also allow disabling GSSAPI, NTLM from UI (requires patch)
			$disable_authenticators = [];
			
			if($account->auth_disable_plain)
				$disable_authenticators[] = 'PLAIN';
			
			if(defined('APP_MAIL_IMAP_DISABLE_NTLM') && APP_MAIL_IMAP_DISABLE_NTLM)
				$disable_authenticators[] = 'NTLM';
			
			if(defined('APP_MAIL_IMAP_DISABLE_GSSAPI') && APP_MAIL_IMAP_DISABLE_GSSAPI)
				$disable_authenticators[] = 'GSSAPI';
			
			if(!empty($disable_authenticators))
				$imap_options['DISABLE_AUTHENTICATOR'] = $disable_authenticators;
			
			$mailboxes_checked++;

			$logger->info('[Mailboxes] Account being parsed is '. $account->name);
			
			$imap_connect = $account->getImapConnectString();

			$mailbox_runtime = microtime(true);
			
			if(false === ($mailbox = @imap_open($imap_connect,
				!empty($account->username)?$account->username:"",
				!empty($account->password)?$account->password:"",
				null,
				0,
				$imap_options
				))) {
				
				$logger->error("[Mailboxes] Failed with error: ".imap_last_error());
				
				// Increment fails
				$num_fails = $account->num_fails + 1;
				$delay_until = time() + (min($num_fails, 15) * 120);
				
				$fields = array(
					DAO_Mailbox::CHECKED_AT => time(),
					DAO_Mailbox::NUM_FAILS => $num_fails,
					DAO_Mailbox::DELAY_UNTIL => $delay_until, // Delay 2 mins per consecutive failure
				);
				
				$logger->error("[Mailboxes] Delaying next mailbox check until ".date('h:i a', $delay_until));
				
				// Notify admins about consecutive mailbox failures at an interval
				if(in_array($num_fails, array(2,5,10,20))) {
					$logger->info(sprintf("[Mailboxes] Sending notification about %d consecutive failures on this mailbox", $num_fails));
					
					$admin_workers = DAO_Worker::getAllAdmins();
					
					/*
					 * Log activity (mailbox.check.error)
					 */
					$entry = array(
						//Mailbox {{target}} has failed to download mail on {{count}} consecutive attempts: {{error}}
						'message' => 'activities.mailbox.check.error',
						'variables' => array(
							'target' => $account->name,
							'count' => $num_fails,
							'error' => imap_last_error(),
							),
						'urls' => array(
							'target' => sprintf("ctx://%s:%s/%s", CerberusContexts::CONTEXT_MAILBOX, $account->id, DevblocksPlatform::strToPermalink($account->name)),
							)
					);
					CerberusContexts::logActivity('mailbox.check.error', CerberusContexts::CONTEXT_MAILBOX, $account->id, $entry, null, null, array_keys($admin_workers));
				}
				
				DAO_Mailbox::update($account->id, $fields);
				continue;
			}
			
			$mailbox_stats = imap_check($mailbox);
			
			// [TODO] Make this an account setting?
			$total = min($max_downloads, $mailbox_stats->Nmsgs);
			
			$logger->info("[Mailboxes] Connected to mailbox '".$account->name."' (".number_format((microtime(true)-$mailbox_runtime)*1000,2)." ms)");

			$mailbox_runtime = microtime(true);
			
			$msgs_stats = imap_fetch_overview($mailbox, sprintf("1:%d", $total));
			
			foreach($msgs_stats as &$msg_stats) {
				$time = microtime(true);

				do {
					$unique = sprintf("%s.%04d",
					time(),
					mt_rand(0,9999)
					);
					$filename = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR . $unique;
				} while(file_exists($filename));

				$fp = fopen($filename,'w+');

				if($fp) {
					$mailbox_xheader = "X-Cerberus-Mailbox: " . $account->name . "\r\n";
					fwrite($fp, $mailbox_xheader);

					// If the message is too big, save a message stating as much
					if($account->max_msg_size_kb && $msg_stats->size >= $account->max_msg_size_kb * 1000) {
						$logger->warn(sprintf("[Mailboxes] This message is %s which exceeds the mailbox limit of %s",
							DevblocksPlatform::strPrettyBytes($msg_stats->size),
							DevblocksPlatform::strPrettyBytes($account->max_msg_size_kb*1000)
						));
						
						$error_msg = sprintf("This %s message exceeded the mailbox limit of %s",
							DevblocksPlatform::strPrettyBytes($msg_stats->size),
							DevblocksPlatform::strPrettyBytes($account->max_msg_size_kb*1000)
						);
						
						$truncated_message = sprintf(
							"X-Cerb-Parser-Error: message-size-limit-exceeded\r\n".
							"X-Cerb-Parser-ErrorMsg: %s\r\n".
							"From: %s\r\n".
							"To: %s\r\n".
							"Subject: %s\r\n".
							"Date: %s\r\n".
							"Message-Id: %s\r\n".
							"\r\n".
							"(%s)\r\n",
							$error_msg,
							$msg_stats->from,
							$msg_stats->to,
							$msg_stats->subject,
							$msg_stats->date,
							$msg_stats->message_id,
							$error_msg
						);
						
						fwrite($fp, $truncated_message);
						
					// Otherwise, save the message like normal
					} else {
						imap_savebody($mailbox, $fp, $msg_stats->msgno); // Write the message directly to the file handle
					}

					@fclose($fp);
				}
				
				$time = microtime(true) - $time;
				
				// If this message took a really long time to download, skip it and retry later
				// [TODO] We may want to keep track if the same message does this repeatedly
				if(($time*1000) > (0.95 * $imap_timeout_read_ms)) {
					$logger->warn("[Mailboxes] This message took more than 95% of the IMAP_READTIMEOUT value to download. We probably timed out. Aborting to retry later...");
					unlink($filename);
					break;
				}
				
				/*
				 * [JAS]: We don't add the .msg extension until we're done with the file,
				 * since this will safely be ignored by the parser until we're ready
				 * for it.
				 */
				rename($filename, dirname($filename) . DIRECTORY_SEPARATOR . basename($filename) . '.msg');

				$logger->info("[Mailboxes] Downloaded message ".$msg_stats->msgno." (".sprintf("%d",($time*1000))." ms)");
				
				imap_delete($mailbox, $msg_stats->msgno);
			}
			
			// Clear the fail count if we had past fails
			DAO_Mailbox::update(
				$account->id,
				array(
					DAO_Mailbox::CHECKED_AT => time(),
					DAO_Mailbox::NUM_FAILS => 0,
					DAO_Mailbox::DELAY_UNTIL => 0,
				)
			);
			
			imap_expunge($mailbox);
			imap_close($mailbox);
			@imap_errors();
			
			$logger->info("[Mailboxes] Closed mailbox (".number_format((microtime(true)-$mailbox_runtime)*1000,2)." ms)");
		}
		
		if(empty($mailboxes_checked))
			$logger->info('[Mailboxes] There are no mailboxes ready to be checked.');
		
		$logger->info("[Mailboxes] Finished Mailbox Checker job (".number_format((microtime(true)-$runtime)*1000,2)." ms)");
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();

		$timeout = ini_get('max_execution_time');
		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		$tpl->display('devblocks:cerberusweb.core::cron/mailbox/config.tpl');
	}

	function saveConfigurationAction() {

		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};

class MailQueueCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		$runtime = microtime(true);

		$stop_time = time() + 30; // [TODO] Make configurable
		$last_id = 0;
		
		$logger->info("[Mail Queue] Starting...");
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Parser] The 'mailparse' extension is not loaded.  Aborting!");
			return false;
		}
		
		// Drafts->SMTP
		
		do {
			$messages = DAO_MailQueue::getWhere(
				sprintf("%s = %d AND %s <= %d AND %s > %d AND %s < %d",
					DAO_MailQueue::IS_QUEUED,
					1,
					DAO_MailQueue::QUEUE_DELIVERY_DATE,
					time(),
					DAO_MailQueue::ID,
					$last_id,
					DAO_MailQueue::QUEUE_FAILS,
					10
				),
				array(DAO_MailQueue::QUEUE_DELIVERY_DATE, DAO_MailQueue::UPDATED),
				array(true, true),
				25
			);
	
			if(!empty($messages)) {
				$message_ids = array_keys($messages);
				
				foreach($messages as $message) { /* @var $message Model_MailQueue */
					if(!$message->send()) {
						// The drafts handle fail counts and retries automatically now
					} else {
						$logger->info(sprintf("[Mail Queue] Sent message %d", $message->id));
					}
				}
				
				$last_id = end($message_ids);
			}
			
		} while(!empty($messages) && $stop_time > time());
		
		$logger->info("[Mail Queue] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
};

/*
 * PARAMS (overloads):
 * parse_max=n (max tickets to parse)
 *
 */
class ParseCron extends CerberusCronPageExtension {
	function scanDirMessages($dir) {
		if(substr($dir,-1,1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$files = glob($dir . '*.msg');
		if ($files === false) return array();
		return $files;
	}

	function run() {
		$logger = DevblocksPlatform::services()->log();
		
		$logger->info("[Parser] Starting Parser Task");
		
		if (!extension_loaded("imap")) {
			$logger->err("[Parser] The 'IMAP' extension is not loaded.  Aborting!");
			return false;
		}
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Parser] The 'mailparse' extension is not loaded.  Aborting!");
			return false;
		}
		
		if (!extension_loaded("mbstring")) {
			$logger->err("[Parser] The 'mbstring' extension is not loaded.  Aborting!");
			return false;
		}

		$runtime = microtime(true);
		
		// Allow runtime overloads (by host, etc.)
		@$opt_parse_max = DevblocksPlatform::importGPC($_REQUEST['parse_max'],'integer');
		
		$total = !empty($opt_parse_max) ? $opt_parse_max : $this->getParam('max_messages', 500);

		$mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
		if ($subdirs === false) $subdirs = array();
		$subdirs[] = $mailDir; // Add our root directory last

		$archivePath = sprintf("%sarchive/%04d/%02d/%02d/",
			APP_MAIL_PATH,
			date('Y'),
			date('m'),
			date('d')
		);
		
		if(defined('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE') && DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE) {
			if(!file_exists($archivePath) && is_writable(APP_MAIL_PATH)) {
				if(false === mkdir($archivePath, 0755, true)) {
					$logger->error("[Parser] Can't write to the archive path: ". $archivePath. " ...skipping copy");
				}
			}
		}
		
		foreach($subdirs as $subdir) {
			if(!is_writable($subdir)) {
				$logger->error('[Parser] Write permission error, unable to parse messages inside: '. $subdir. " ...skipping");
				continue;
			}

			$files = $this->scanDirMessages($subdir);
			
			foreach($files as $file) {
				$filePart = basename($file);

				if(defined('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE') && DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE) {
					if(!copy($file, $archivePath.$filePart)) {
						//...
					}
				}
				
				if(!is_readable($file)) {
					$logger->error('[Parser] Read permission error, unable to parse ' . $file . " ...skipping");
					continue;
				}

				if(!is_writable($file)) {
					$logger->error('[Parser] Write permission error, unable to parse ' . $file . " ...skipping");
					continue;
				}
				
				$parseFile = sprintf("%s/fail/%s",
					APP_MAIL_PATH,
					$filePart
				);
				rename($file, $parseFile);
				
				$this->_parseFile($parseFile);

				if(--$total <= 0) break;
			}
			if($total <= 0) break;
		}
		
		unset($files);
		unset($subdirs);
		
		$logger->info("[Parser] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function _parseFile($full_filename) {
		$logger = DevblocksPlatform::services()->log('Parser');
		
		$fileparts = pathinfo($full_filename);
		$logger->info("Reading ".$fileparts['basename']."...");

		$time = microtime(true);

		if(false == ($message = CerberusParser::parseMimeFile($full_filename))) {
			$logger->error(sprintf("%s failed to parse and it has been saved to the storage/mail/fail/ directory.", $fileparts['basename']));
			return;
		}

		$time = microtime(true) - $time;
		$logger->info("Decoded! (".sprintf("%d",($time*1000))." ms)");

		$time = microtime(true);
		$ticket_id = CerberusParser::parseMessage($message);
		$time = microtime(true) - $time;
		
		$logger->info("Parsed! (".sprintf("%d",($time*1000))." ms) " .
			(!empty($ticket_id) ? ("(Ticket ID: ".$ticket_id.")") : ("(Local Delivery Rejected.)")));

		if(is_bool($ticket_id) && false === $ticket_id) {
			// Leave the message in storage/mail/fail
			$logger->error(sprintf("%s failed to parse and it has been saved to the storage/mail/fail/ directory.", $fileparts['basename']));
			
			// [TODO] Admin notification?
			
		} else {
			@unlink($full_filename);
			$logger->info("The message source has been removed.");
		}
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('max_messages', $this->getParam('max_messages', 500));

		$tpl->display('devblocks:cerberusweb.core::cron/parser/config.tpl');
	}

	function saveConfigurationAction() {
		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);
	}
};