<?php
/***********************************************************************
 | Cerb(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2012, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Devblocks Public License.
 | The latest version of this license can be found here:
 | http://cerberusweb.com/license
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/

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
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Parser] Starting Parser Task");
		
		if (!extension_loaded("imap")) {
			$logger->err("[Parser] The 'IMAP' extension is not loaded.  Aborting!");
			return false;
		}
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Parser] The 'mailparse' extension is not loaded.  Aborting!");
			return false;
		}

		$timeout = ini_get('max_execution_time');
		$runtime = microtime(true);
		 
		// Allow runtime overloads (by host, etc.)
		@$gpc_parse_max = DevblocksPlatform::importGPC($_REQUEST['parse_max'],'integer');
		
		$total = !empty($gpc_parse_max) ? $gpc_parse_max : $this->getParam('max_messages', 500);

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
		$logger = DevblocksPlatform::getConsoleLog('Parser');
		
		$fileparts = pathinfo($full_filename);
		$logger->info("Reading ".$fileparts['basename']."...");

		$time = microtime(true);

		$mime = mailparse_msg_parse_file($full_filename);
		$message = CerberusParser::parseMime($mime, $full_filename);

		$time = microtime(true) - $time;
		$logger->info("Decoded! (".sprintf("%d",($time*1000))." ms)");

		//		echo "<b>Plaintext:</b> ", $message->body,"<BR>";
		//		echo "<BR>";
		//		echo "<b>HTML:</b> ", htmlspecialchars($message->htmlbody), "<BR>";
		//		echo "<BR>";
		//		echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
		//		echo "<HR>";

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
		
		mailparse_msg_free($mime);

		//		flush();
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('max_messages', $this->getParam('max_messages', 500));

		$tpl->display('devblocks:cerberusweb.core::cron/parser/config.tpl');
	}

	function saveConfigurationAction() {
		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);
	}
};

/*
 * PARAMS (overloads):
 * maint_max_deletes=n (max tickets to purge)
 *
 */
// [TODO] Clear idle temp files (fileatime())
class MaintCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Maint] Starting Maintenance Task");
		
		$db = DevblocksPlatform::getDatabaseService();

		// Platform
		DAO_Platform::maint();
		
		// Purge Deleted Content
		$purge_waitdays = intval($this->getParam('purge_waitdays', 7));
		$purge_waitsecs = time() - (intval($purge_waitdays) * 86400);

		$sql = sprintf("DELETE FROM ticket ".
			"WHERE is_deleted = 1 ".
			"AND updated_date < %d ",
			$purge_waitsecs
		);
		$db->Execute($sql);
		
		$logger->info("[Maint] Purged " . $db->Affected_Rows() . " ticket records.");

		// Give plugins a chance to run maintenance (nuke NULL rows, etc.)
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'cron.maint',
				array()
			)
		);
		
		// Nuke orphaned words from the Bayes index
		// [TODO] Make this configurable from job
		$sql = "DELETE FROM bayes_words WHERE nonspam + spam < 2"; // only 1 occurrence
		$db->Execute($sql);

		$logger->info('[Maint] Purged ' . $db->Affected_Rows() . ' obscure spam words.');
		
		// [mdf] Remove any empty directories inside storage/mail/new
		$mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
		if ($subdirs !== false) {
			foreach($subdirs as $subdir) {
				$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
				if($directory_empty && is_writeable($subdir)) {
					rmdir($subdir);
				}
			}
		}
		
		$logger->info('[Maint] Cleaned up mail directories.');
	  
		// [JAS] Remove any empty directories inside storage/import/new
		$importNewDir = APP_STORAGE_PATH . '/import/new' . DIRECTORY_SEPARATOR;
		$subdirs = glob($importNewDir . '*', GLOB_ONLYDIR);
		if ($subdirs !== false) {
			foreach($subdirs as $subdir) {
				$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
				if($directory_empty && is_writeable($subdir)) {
					rmdir($subdir);
				}
			}
		}
		$logger->info('[Maint] Cleaned up import directories.');
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));

		$tpl->display('devblocks:cerberusweb.core::cron/maint/config.tpl');
	}

	function saveConfigurationAction() {
		@$purge_waitdays = DevblocksPlatform::importGPC($_POST['purge_waitdays'],'integer');
		$this->setParam('purge_waitdays', $purge_waitdays);
	}
};

/**
 * Plugins can implement an event listener on the heartbeat to do any kind of
 * time-dependent or interval-based events.  For example, doing a workflow
 * action every 5 minutes.
 */
class HeartbeatCron extends CerberusCronPageExtension {
	function run() {
		// Heartbeat Event
		$eventMgr = DevblocksPlatform::getEventService();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'cron.heartbeat',
				array(
				)
			)
		);
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::cron/heartbeat/config.tpl');
	}
};

class ImportCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Importer] Starting Import Task");
		
		@set_time_limit(1200); // 20m
		 
		$importNewDir = APP_STORAGE_PATH . '/import/new/';
		$importFailDir = APP_STORAGE_PATH . '/import/fail/';

		if(!is_writable($importNewDir)) {
			$logger->error("[Importer] Unable to write in '$importNewDir'.  Please check permissions.");
			return;
		}

		if(!is_writable($importFailDir)) {
			$logger->error("[Importer] Unable to write in '$importFailDir'.  Please check permissions.");
			return;
		}

		if (!extension_loaded("imap")) {
			$logger->err("[Parser] The 'IMAP' extension is not loaded.  Aborting!");
			return false;
		}
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Parser] The 'mailparse' extension is not loaded.  Aborting!");
			return false;
		}
		
		$limit = 100; // [TODO] Set from config

		$runtime = microtime(true);

		$subdirs = glob($importNewDir . '*', GLOB_ONLYDIR);
		if ($subdirs === false) $subdirs = array();
		$subdirs[] = $importNewDir; // Add our root directory last

		foreach($subdirs as $subdir) {
			if(!is_writable($subdir)) {
				$logger->error('[Importer] Write permission error, unable parse imports inside: '. $subdir. "...skipping");
				continue;
			}

			$files = $this->scanDirMessages($subdir);
			 
			foreach($files as $file) {
				// If we can't nuke the file, there's no sense in trying to import it
				if(!is_writeable($file))
					continue;

				// Preventatively move into the fail dir while we parse
				$move_to_dir = $importFailDir . basename($subdir) . '/';

				if(!file_exists($move_to_dir))
					mkdir($move_to_dir,0744,true);

				$dest_file = $move_to_dir . basename($file);
				@rename($file, $dest_file);
				$file = $dest_file;
				
				// Parse the XML
				if(!@$xml_root = simplexml_load_file($file)) { /* @var $xml_root SimpleXMLElement */
					$logger->error("[Importer] Error parsing XML file: " . $file);
					continue;
				}
				
				if(empty($xml_root)) {
					$logger->error("[Importer] XML root element doesn't exist in: " . $file);
					continue;
				}
				
				$object_type = $xml_root->getName();

				$file_part = basename($file);
				
				$logger->info("[Importer] Reading ".$file_part." ... ($object_type)");

				if($this->_handleImport($object_type, $xml_root)) { // Success
					@unlink($file);
				}
				 
				if(--$limit <= 0)
				break;
			}
				
			if($limit <= 0)
			break;
		}
	  
		unset($files);
		unset($subdirs);

		$logger->info("[Importer] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
		
		@imap_errors();
	}

	private function _handleImport($object_type, $xml) {
		// [TODO] Import extensions (delegate to plugins)
		switch($object_type) {
		 	case 'comment':
		 		return $this->_handleImportComment($xml);
		 		break;
		 	case 'kbarticle':
		 		return $this->_handleImportKbArticle($xml);
		 		break;
		 	case 'ticket':
		 		return $this->_handleImportTicket($xml);
		 		break;
		 	case 'worker':
		 		return $this->_handleImportWorker($xml);
		 		break;
		 	case 'organization':
		 		return $this->_handleImportOrg($xml);
		 		break;
		 	case 'contact':
		 		return $this->_handleImportAddress($xml);
		 		break;
		 	default:
		 		break;
		 }
	}
	
	/* _handleImportKbArticle */
	private function _getCategoryChildByName($list, $node, $name) {
		if(is_array($node))
		foreach($node as $child_id => $null) {
			if(isset($list[$child_id]) && 0 == strcasecmp($list[$child_id]->name,$name))
				return $child_id;
		}
		
		return NULL;
	}
	
	private function _handleImportKbArticle($xml) {
		static $categoryList = NULL;
		static $categoryMap = NULL;
		
		$title = (string) $xml->title;
		$created = intval((string) $xml->created_date);
		$content_b64 = (string) $xml->content;

		// Bad file
		if(empty($content_b64) || empty($title)) {
			return false;
		}

		if(NULL == $categoryMap || NULL == $categoryList) {
			$categoryList = DAO_KbCategory::getAll();
			$categoryMap = DAO_KbCategory::getTreeMap();
		}
		
		// Handle multiple <categories> elements
		$categoryIds = array();
		foreach($xml->categories as $eCategories) {
			$pid = 0;
			$ptr =& $categoryMap[$pid];
			$categoryId = 0;
			
			foreach($eCategories->category as $eCategory) {
				$catName = (string) $eCategory;
				
	//			echo "Looking for '", $catName, "' under $pid ...<br>";
				
				if(NULL == ($categoryId = $this->_getCategoryChildByName($categoryList, $ptr, $catName))) {
					$fields = array(
						DAO_KbCategory::NAME => $catName,
						DAO_KbCategory::PARENT_ID => $pid,
					);
					$categoryId = DAO_KbCategory::create($fields);
	//				echo " - Not found, inserted as $categoryId<br>";
					
					$categoryList[$categoryId] = DAO_KbCategory::get($categoryId);
					
					if(!isset($categoryMap[$pid]))
						$categoryMap[$pid] = array();
						
					$categoryMap[$pid][$categoryId] = 0;
					$categoryMap[$categoryId] = array();
					$categoryIds[] = $categoryId;
					
				} else {
					$categoryIds[] = $categoryId;
	//				echo " - Found at $categoryId !<br>";
					
				}
				
				$pid = $categoryId;
				$ptr =& $categoryMap[$categoryId];
			}
		}
		
		// Decode content
		$content = base64_decode($content_b64);

		// [TODO] Dupe check?  (title in category)
		
		$fields = array(
			DAO_KbArticle::TITLE => $title,
			DAO_KbArticle::UPDATED => $created,
			DAO_KbArticle::FORMAT => 1, // HTML
			DAO_KbArticle::CONTENT => $content,
			DAO_KbArticle::VIEWS => 0, // [TODO]
		);

		if(null !== ($articleId = DAO_KbArticle::create($fields))) {
			DAO_KbArticle::setCategories($articleId, $categoryIds, false);
			return true;
		}
		
		return false;
	}
	
	// [TODO] Move to an extension
	private function _handleImportTicket($xml) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$logger = DevblocksPlatform::getConsoleLog();
		$workers = DAO_Worker::getAll();

		static $email_to_worker_id = null;
		static $group_name_to_id = null;
		static $bucket_name_to_id = null;
		
		// Hash Workers so we can ID their incoming tickets
		if(null == $email_to_worker_id) {
			$email_to_worker_id = array();
			
			if(is_array($workers))
			foreach($workers as $worker) { /* @var $worker Model_Worker */
				$email_to_worker_id[strtolower($worker->email)] = intval($worker->id);
			}
		}
		
		// Hash Group names
		if(null == $group_name_to_id) {
			$groups = DAO_Group::getAll();
			$group_name_to_id = array();

			if(is_array($groups))
			foreach($groups as $group) {
				$group_name_to_id[strtolower($group->name)] = intval($group->id);
			}
		}
		
		// Hash Bucket names
		if(null == $bucket_name_to_id) {
			$buckets = DAO_Bucket::getAll();
			$bucket_name_to_id = array();

			if(is_array($buckets))
			foreach($buckets as $bucket) { /* @var $bucket Model_Bucket */
				// Hash by group ID and bucket name
				$hash = md5($bucket->group_id . strtolower($bucket->name));
				$bucket_to_id[$hash] = intval($bucket->id);
			}
		}
		
		$sMask = (string) $xml->mask;
		$sSubject = substr((string) $xml->subject,0,255);
		$sGroup = (string) $xml->group;
		$sBucket = (string) $xml->bucket;
		$iCreatedDate = (integer) $xml->created_date;
		$iUpdatedDate = (integer) $xml->updated_date;
		$isWaiting = (integer) $xml->is_waiting;
		$isClosed = (integer) $xml->is_closed;
		
		if(empty($sMask)) {
			$sMask = CerberusApplication::generateTicketMask();
		}
		
		// Find the destination Group + Bucket (or create them)
		if(empty($sGroup)) {
			$iDestGroupId = 0;
			
			if(null != ($iDestGroup = DAO_Group::getDefaultGroup()))
				$iDestGroupId = $iDestGroup->id;
			
		} elseif(null == ($iDestGroupId = @$group_name_to_id[strtolower($sGroup)])) {
			$iDestGroupId = DAO_Group::create(array(
				DAO_Group::NAME => $sGroup,
			));
			
			// Give all superusers manager access to this new group
			if(is_array($workers))
			foreach($workers as $worker) {
				if($worker->is_superuser)
					DAO_Group::setGroupMember($iDestGroupId,$worker->id,true);
			}
			
			// Rehash
			DAO_Group::getAll(true);
			$group_name_to_id[strtolower($sGroup)] = $iDestGroupId;
		}
		
		if(empty($sBucket)) {
			$iDestBucketId = 0; // Inbox
			
		} elseif(null == ($iDestBucketId = @$bucket_name_to_id[md5($iDestGroupId.strtolower($sBucket))])) {
			$iDestBucketId = DAO_Bucket::create($sBucket, $iDestGroupId);
			
			// Rehash
			DAO_Bucket::getAll(true);
			$bucket_name_to_id[strtolower($sBucket)] = $iDestBucketId;
		}
			
		// Xpath the first and last "from" out of "/ticket/messages/message/headers/from"
		$aMessageNodes = $xml->xpath("/ticket/messages/message");
		$iNumMessages = count($aMessageNodes);
		
		@$eFirstMessage = reset($aMessageNodes);
		
		if(is_null($eFirstMessage)) {
			$logger->warning('[Importer] Ticket ' . $sMask . " doesn't have any messages.  Skipping.");
			return false;
		}
		
		if(is_null($eFirstMessage->headers) || is_null($eFirstMessage->headers->from)) {
			$logger->warning('[Importer] Ticket ' . $sMask . " first message doesn't provide a sender address.");
			return false;
		}

		$sFirstWrote = self::_parseRfcAddressList($eFirstMessage->headers->from, true);
		
		if(null == ($firstWroteInst = CerberusApplication::hashLookupAddress($sFirstWrote, true))) {
			$logger->warning('[Importer] Ticket ' . $sMask . " - Invalid sender adddress: " . $sFirstWrote);
			return false;
		}
		
		$eLastMessage = end($aMessageNodes);
		
		if(is_null($eLastMessage)) {
			$logger->warning('[Importer] Ticket ' . $sMask . " doesn't have any messages.  Skipping.");
			return false;
		}
		
		if(is_null($eLastMessage->headers) || is_null($eLastMessage->headers->from)) {
			$logger->warning('[Importer] Ticket ' . $sMask . " last message doesn't provide a sender address.");
			return false;
		}
		
		$sLastWrote = self::_parseRfcAddressList($eLastMessage->headers->from, true);
		
		if(null == ($lastWroteInst = CerberusApplication::hashLookupAddress($sLastWrote, true))) {
			$logger->warning('[Importer] Ticket ' . $sMask . ' last message has an invalid sender address: ' . $sLastWrote);
			return false;
		}

		// Last action code + last worker
		$sLastActionCode = CerberusTicketActionCode::TICKET_OPENED;
		if($iNumMessages > 1) {
			if(isset($email_to_worker_id[strtolower($lastWroteInst->email)])) {
				$sLastActionCode = CerberusTicketActionCode::TICKET_WORKER_REPLY;
			} else {
				$sLastActionCode = CerberusTicketActionCode::TICKET_CUSTOMER_REPLY;
			}
		}
		
		// Dupe check by ticket mask
		if(null != DAO_Ticket::getTicketByMask($sMask)) {
			$logger->warning("[Importer] Ticket mask '" . $sMask . "' already exists.  Making it unique.");
			
			$uniqueness = 1;
			$origMask = $sMask;
			
			// Append new uniqueness to the ticket mask:  LLL-NNNNN-NNN-1, LLL-NNNNN-NNN-2, ...
			do {
				$sMask = $origMask . '-' . ++$uniqueness;
			} while(null != DAO_Ticket::getTicketIdByMask($sMask));
			
			$logger->info("[Importer] The unique mask for '".$origMask."' is now '" . $sMask . "'");
		}
		
		// Create ticket
		$fields = array(
			DAO_Ticket::MASK => $sMask,
			DAO_Ticket::SUBJECT => $sSubject,
			DAO_Ticket::IS_WAITING => $isWaiting,
			DAO_Ticket::IS_CLOSED => $isClosed,
			DAO_Ticket::FIRST_WROTE_ID => intval($firstWroteInst->id),
			DAO_Ticket::LAST_WROTE_ID => intval($lastWroteInst->id),
			DAO_Ticket::ORG_ID => intval($firstWroteInst->contact_org_id),
			DAO_Ticket::CREATED_DATE => $iCreatedDate,
			DAO_Ticket::UPDATED_DATE => $iUpdatedDate,
			DAO_Ticket::GROUP_ID => intval($iDestGroupId),
			DAO_Ticket::BUCKET_ID => intval($iDestBucketId),
			DAO_Ticket::LAST_ACTION_CODE => $sLastActionCode,
		);
		$ticket_id = DAO_Ticket::create($fields);

//		echo "Ticket: ",$ticket_id,"<BR>";
//		print_r($fields);
		
		// Create requesters
		if(!is_null($xml->requesters))
		foreach($xml->requesters->address as $eAddress) { /* @var $eAddress SimpleXMLElement */
			$sRequesterAddy = (string) $eAddress; // [TODO] RFC822
			
			// Insert requesters
			DAO_Ticket::createRequester($sRequesterAddy, $ticket_id);
		}
		
		// Create messages
		if(!is_null($xml->messages)) {
			$count_messages = count($xml->messages->message);
			$seek_messages = 1;
			foreach($xml->messages->message as $eMessage) { /* @var $eMessage SimpleXMLElement */
				$eHeaders =& $eMessage->headers; /* @var $eHeaders SimpleXMLElement */
	
				$sMsgFrom = (string) $eHeaders->from;
				$sMsgDate = (string) $eHeaders->date;
				
				$sMsgFrom = self::_parseRfcAddressList($sMsgFrom, true);
				
				if(NULL == $sMsgFrom) {
					$logger->warning('[Importer] Ticket ' . $sMask . ' - Invalid message sender: ' . $sMsgFrom . ' (skipping)');
					continue;
				}
				
				if(null == ($msgFromInst = CerberusApplication::hashLookupAddress($sMsgFrom, true))) {
					$logger->warning('[Importer] Ticket ' . $sMask . ' - Invalid message sender: ' . $sMsgFrom . ' (skipping)');
					continue;
				}
	
				@$msgWorkerId = intval($email_to_worker_id[strtolower($msgFromInst->email)]);
	//			$logger->info('Checking if '.$msgFromInst->email.' is a worker');
				
				$fields = array(
					DAO_Message::TICKET_ID => $ticket_id,
					DAO_Message::CREATED_DATE => strtotime($sMsgDate),
					DAO_Message::ADDRESS_ID => $msgFromInst->id,
					DAO_Message::IS_OUTGOING => !empty($msgWorkerId) ? 1 : 0,
					DAO_Message::WORKER_ID => !empty($msgWorkerId) ? $msgWorkerId : 0,
				);
				$email_id = DAO_Message::create($fields);
				
				// First thread
				if(1==$seek_messages) {
					DAO_Ticket::update($ticket_id,array(
						DAO_Ticket::FIRST_MESSAGE_ID => $email_id
					));
				}
				
				// Last thread
				if($count_messages==$seek_messages) {
					DAO_Ticket::update($ticket_id,array(
						DAO_Ticket::LAST_MESSAGE_ID => $email_id
					));
				}
	
				// Create attachments
				if(!is_null($eMessage->attachments))
				foreach($eMessage->attachments->attachment as $eAttachment) { /* @var $eAttachment SimpleXMLElement */
					$sFileName = (string) $eAttachment->name;
					$sMimeType = (string) $eAttachment->mimetype;
					$sFileSize = (integer) $eAttachment->size;
					$sFileContentB64 = (string) $eAttachment->content;
					
					// [TODO] This could be a little smarter about detecting extensions
					if(empty($sMimeType))
						$sMimeType = "application/octet-stream";
					
					$sFileContent = base64_decode($sFileContentB64);
					unset($sFileContentB64);
					
					$fields = array(
						DAO_Attachment::DISPLAY_NAME => $sFileName,
						DAO_Attachment::MIME_TYPE => $sMimeType,
					);
					$file_id = DAO_Attachment::create($fields);
					
					// Link
					DAO_AttachmentLink::create($file_id, CerberusContexts::CONTEXT_MESSAGE, $email_id);
					
					// Write file to storage
					Storage_Attachments::put($file_id, $sFileContent);
					unset($sFileContent);
				}
				
				// Create message content
				$sMessageContentB64 = (string) $eMessage->content;
				$sMessageContent = base64_decode($sMessageContentB64);
				
				// Content-type specific handling
				if(isset($eMessage->content['content-type'])) { // do we have a content-type?
					if(strtolower($eMessage->content['content-type']) == 'html') { // html?
						// Force to plaintext part
						$sMessageContent = DevblocksPlatform::stripHTML($sMessageContent);
					}
				}
				unset($sMessageContentB64);
				
				Storage_MessageContent::put($email_id, $sMessageContent);
				unset($sMessageContent);
	
				// Headers
				foreach($eHeaders->children() as $eHeader) { /* @var $eHeader SimpleXMLElement */
					DAO_MessageHeader::create($email_id, $eHeader->getName(), (string) $eHeader);
				}
				
				$seek_messages++;
			}
		}
		
		// Create comments
		if(!is_null($xml->comments))
		foreach($xml->comments->comment as $eComment) { /* @var $eMessage SimpleXMLElement */
			$iCommentDate = (integer) $eComment->created_date;
			$sCommentAuthor = (string) $eComment->author; // [TODO] Address Hash Lookup
			
			$sCommentTextB64 = (string) $eComment->content;
			$sCommentText = base64_decode($sCommentTextB64);
			unset($sCommentTextB64);
			
			$commentAuthorInst = CerberusApplication::hashLookupAddress($sCommentAuthor, true);
			
			// [TODO] Sanity checking
			
			$fields = array(
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_Comment::CONTEXT_ID => intval($ticket_id),
				DAO_Comment::CREATED => intval($iCommentDate),
				DAO_Comment::ADDRESS_ID => intval($commentAuthorInst->id),
				DAO_Comment::COMMENT => $sCommentText,
			);
			$comment_id = DAO_Comment::create($fields);
			
			unset($sCommentText);
		}
		
		$logger->info('[Importer] Imported ticket #'.$ticket_id);
		
		return true;
	}
	
	private function _parseRfcAddressList($addressStr, $only_one) {
			// Need to parse the 'From' header as RFC-2822: "name" <user@domain.com>
			@$rfcAddressList = imap_rfc822_parse_adrlist($addressStr, 'host');
			
			if(!is_array($rfcAddressList) || empty($rfcAddressList))
				return NULL;
			

			$addresses = array();
			foreach($rfcAddressList as $rfcAddress) {
				if(empty($rfcAddress->host) || $rfcAddress->host == 'host') {
					continue;
				}
				$addresses[] =  trim(strtolower($rfcAddress->mailbox.'@'.$rfcAddress->host));
			}
			
			if(empty($addresses)) {
				return NULL;
			}
			
			$result = ($only_one) ? $addresses[0] : $addresses;
			return $result;
	}

	private function _handleImportWorker($xml) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$logger = DevblocksPlatform::getConsoleLog();

		$sFirstName = (string) $xml->first_name;
		$sLastName = (string) $xml->last_name;
		$sEmail = (string) $xml->email;
		$sPassword = (string) $xml->password;
		$isSuperuser = (integer) $xml->is_superuser;
		
		// Dupe check worker email
		if(null != ($worker_id = DAO_Worker::getByEmail($sEmail))) {
			$logger->info('[Importer] Avoiding creating duplicate worker #'.$worker_id.' ('.$sEmail.')');
			return true;
		}
		
		$fields = array(
			DAO_Worker::EMAIL => $sEmail,
			DAO_Worker::PASSWORD => $sPassword, // pre-MD5'd
			DAO_Worker::FIRST_NAME => $sFirstName,
			DAO_Worker::LAST_NAME => $sLastName,
			DAO_Worker::IS_SUPERUSER => intval($isSuperuser),
			DAO_Worker::AUTH_EXTENSION_ID => 'login.password',
		);
		$worker_id = DAO_Worker::create($fields);
		
		// Address to Worker
		DAO_AddressToWorker::assign($sEmail, $worker_id);
		DAO_AddressToWorker::update($sEmail,array(
			DAO_AddressToWorker::IS_CONFIRMED => 1
		));
		
		$logger->info('[Importer] Imported worker #'.$worker_id.' ('.$sEmail.')');
		
		DAO_Worker::clearCache();
		
		return true;
	}

	private function _handleImportOrg($xml) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$logger = DevblocksPlatform::getConsoleLog();

		$sName = (string) $xml->name;
		$sStreet = (string) $xml->street;
		$sCity = (string) $xml->city;
		$sProvince = (string) $xml->province;
		$sPostal = (string) $xml->postal;
		$sCountry = (string) $xml->country;
		$sPhone = (string) $xml->phone;
		$sWebsite = (string) $xml->website;
		
		// Dupe check org
		if(null != ($org_id = DAO_ContactOrg::lookup($sName))) {
			$logger->info('[Importer] Avoiding creating duplicate org #'.$org_id.' ('.$sName.')');
			return true;
		}
		
		$fields = array(
			DAO_ContactOrg::NAME => $sName,
			DAO_ContactOrg::STREET => $sStreet,
			DAO_ContactOrg::CITY => $sCity,
			DAO_ContactOrg::PROVINCE => $sProvince,
			DAO_ContactOrg::POSTAL => $sPostal,
			DAO_ContactOrg::COUNTRY => $sCountry,
			DAO_ContactOrg::PHONE => $sPhone,
			DAO_ContactOrg::WEBSITE => $sWebsite,
		);
		$org_id = DAO_ContactOrg::create($fields);
		
		$logger->info('[Importer] Imported org #'.$org_id.' ('.$sName.')');
		
		return true;
	}

	private function _handleImportAddress($xml) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$logger = DevblocksPlatform::getConsoleLog();

		$sFirstName = (string) $xml->first_name;
		$sLastName = (string) $xml->last_name;
		$sEmail = (string) $xml->email;
		$sPassword = (string) $xml->password;
		$sOrganization = (string) $xml->organization;
		
		$addy_exists = false;
		// Dupe check org
		if(null != ($address = DAO_Address::lookupAddress($sEmail))) {
			$logger->info('[Importer] Avoiding creating duplicate contact #'.$address->id.' ('.$sEmail.')');
			$addy_exists = true;
			$address_id = $address->id;
		}
		
		if(!$addy_exists) {
			$fields = array(
				DAO_Address::FIRST_NAME => $sFirstName,
				DAO_Address::LAST_NAME => $sLastName,
				DAO_Address::EMAIL => $sEmail,
			);
			$address_id = DAO_Address::create($fields);
		}
		
		if(!empty($sPassword)) {
			if(null == ($contact = DAO_ContactPerson::getWhere(sprintf("%s = %d", DAO_ContactPerson::EMAIL_ID, $address_id)))) {
				$salt = CerberusApplication::generatePassword(8);
				$fields = array(
					DAO_ContactPerson::EMAIL_ID => $address_id,
					DAO_ContactPerson::LAST_LOGIN => time(),
					DAO_ContactPerson::CREATED => time(),
					DAO_ContactPerson::AUTH_SALT => $salt,
					DAO_ContactPerson::AUTH_PASSWORD => md5($salt.$sPassword)
				);
				
				$contact_person_id = DAO_ContactPerson::create($fields);
				
				DAO_Address::update($address_id, array(
					DAO_Address::CONTACT_PERSON_ID => $contact_person_id
				));
				$logger->info('[Importer] Imported contact '. $sEmail);
			}
		}
		
		// Associate with organization
		if(!empty($sOrganization)) {
			if(null != ($org_id = DAO_ContactOrg::lookup($sOrganization, true))) {
				DAO_Address::update($address_id, array(
					DAO_Address::CONTACT_ORG_ID => $org_id
				));
				$logger->info('[Importer] Associated address '.$sEmail.' with org '.$sOrganization);
			}
		}
		
		$logger->info('[Importer] Imported address #'.$address_id.' ('.$sEmail.')');
		
		return true;
	}
	
	// [TODO] Move to an extension
	private function _handleImportComment($xml) {
		$mask = (string) $xml->mask;
		$author_email = (string) $xml->author_email;
		$note = trim((string) $xml->note);
		$created = intval((string) $xml->created_date);

		$author_address = CerberusApplication::hashLookupAddress($author_email,true);

		// Bad file
		if(empty($note) || empty($author_address) || empty($mask)) {
			return false;
		}

//		echo "MASK: ",$mask,"<BR>";
//		echo " -- Author: ",$author_address->email,"<BR>";
//		echo " -- Note: ",$note,"<BR>";

		if(null !== ($ticket = DAO_Ticket::getTicketByMask($mask))) {
			$fields = array(
				DAO_Comment::CREATED => $created,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_TICKET,
				DAO_Comment::CONTEXT_ID => $ticket->id,
				DAO_Comment::COMMENT => $note,
				DAO_Comment::ADDRESS_ID => $author_address->id,
			);
			
			if(null !== ($comment_id = DAO_Comment::create($fields)))
				return true;
		}
		
		return false;
	}

	function scanDirMessages($dir) {
		if(substr($dir,-1,1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$files = glob($dir . '*.xml');
		if ($files === false) return array();
		return $files;
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->display('devblocks:cerberusweb.core::cron/import/config.tpl');
	}
};

/*
 * PARAMS (overloads):
 * pop3_max=n (max messages to download at once)
 *
 */
class Pop3Cron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[POP3] Starting POP3 Task");
		
		if (!extension_loaded("imap")) {
			$logger->err("[Parser] The 'IMAP' extension is not loaded.  Aborting!");
			return false;
		}
		
		if (!extension_loaded("mailparse")) {
			$logger->err("[Parser] The 'mailparse' extension is not loaded.  Aborting!");
			return false;
		}
		
		@set_time_limit(1200); // 20m

		$accounts = DAO_Pop3Account::getPop3Accounts(); /* @var $accounts Model_Pop3Account[] */

		$timeout = ini_get('max_execution_time');
		
		// Allow runtime overloads (by host, etc.)
		@$gpc_pop3_max = DevblocksPlatform::importGPC($_REQUEST['pop3_max'],'integer');
		
		$max_downloads = !empty($gpc_pop3_max) ? $gpc_pop3_max : $this->getParam('max_messages', (($timeout) ? 20 : 50));
		
		// [JAS]: Make sure our output directory is writeable
		if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
			$logger->error("[POP3] The mail storage directory is not writeable.  Skipping POP3 download.");
			return;
		}

		if(is_array($accounts))
		foreach ($accounts as $account) { /* @var $account Model_Pop3Account */
			if(!$account->enabled)
				continue;

			$logger->info('[POP3] Account being parsed is '. $account->nickname);
			 
			switch($account->protocol) {
				default:
				case 'pop3': // 110
					$connect = sprintf("{%s:%d/pop3/notls}INBOX",
					$account->host,
					$account->port
					);
					break;
					 
				case 'pop3-ssl': // 995
					$connect = sprintf("{%s:%d/pop3/ssl/novalidate-cert}INBOX",
					$account->host,
					$account->port
					);
					break;
					 
				case 'imap': // 143
					$connect = sprintf("{%s:%d/notls}INBOX",
					$account->host,
					$account->port
					);
					break;

				case 'imap-ssl': // 993
					$connect = sprintf("{%s:%d/imap/ssl/novalidate-cert}INBOX",
					$account->host,
					$account->port
					);
					break;
			}

			$runtime = microtime(true);
			 
			if(false === ($mailbox = @imap_open($connect,
			!empty($account->username)?$account->username:"",
			!empty($account->password)?$account->password:""))) {
				$logger->error("[POP3] Failed with error: ".imap_last_error());
				
				// Increment fails
				$num_fails = $account->num_fails + 1;
				$fields = array(
					DAO_Pop3Account::NUM_FAILS => $num_fails,
				);
				
				// Automatically disable POP3s that fail 5+ times
				if($num_fails >= 5) {
					$fields[DAO_Pop3Account::ENABLED] = 0;
					
					$url_writer = DevblocksPlatform::getUrlService();
					$workers = DAO_Worker::getAll();
					
					foreach($workers as $worker) {
						// Only admins
						if(!$worker->is_superuser)
							continue;
						
						$notify_fields = array(
							DAO_Notification::CONTEXT => null,
							DAO_Notification::CONTEXT_ID => null,
							DAO_Notification::CREATED_DATE => time(),
							DAO_Notification::MESSAGE => sprintf("Mailbox '%s' had more than 5 connection errors and was automatically disabled: %s",
								$account->nickname,
								imap_last_error()
							),
							DAO_Notification::URL => $url_writer->write('c=config&a=mail_pop3', true),
							DAO_Notification::WORKER_ID => $worker->id,
						);
						DAO_Notification::create($notify_fields);
					}
				}
				
				DAO_Pop3Account::updatePop3Account($account->id, $fields);
				continue;
			}
			 
			$messages = array();
			$check = imap_check($mailbox);
			 
			// [TODO] Make this an account setting?
			$total = min($max_downloads, $check->Nmsgs);
			 
			$logger->info('[POP3] Init time: '.number_format((microtime(true)-$runtime)*1000,2)," ms");

			$runtime = microtime(true);

			for($i=1;$i<=$total;$i++) {
				/*
				 * [TODO] Logic for max message size (>1MB, etc.) handling.  If over a
				 * threshold then use the attachment parser (imap_fetchstructure) to toss
				 * non-plaintext until the message fits.
				 */
				 
				$msgno = $i;
				 
				$time = microtime(true);
				 
				$headers = imap_fetchheader($mailbox, $msgno);
				$body = imap_body($mailbox, $msgno);

				do {
					$unique = sprintf("%s.%04d",
					time(),
					mt_rand(0,9999)
					);
					$filename = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR . $unique;
				} while(file_exists($filename));

				$fp = fopen($filename,'w');

				if($fp) {
					fwrite($fp,"X-Cerberus-Mailbox: " . $account->nickname . "\r\n");
					fwrite($fp,$headers,strlen($headers));
					fwrite($fp,"\r\n\r\n");
					fwrite($fp,$body,strlen($body));
					@fclose($fp);
				}

				/*
				 * [JAS]: We don't add the .msg extension until we're done with the file,
				 * since this will safely be ignored by the parser until we're ready
				 * for it.
				 */
				rename($filename, dirname($filename) .DIRECTORY_SEPARATOR . basename($filename) . '.msg');

				unset($headers);
				unset($body);

				$time = microtime(true) - $time;
				$logger->info("[POP3] Downloaded message ".$msgno." (".sprintf("%d",($time*1000))." ms)");
				
				imap_delete($mailbox, $msgno);
			}
			
			// Clear the fail count if we had past fails
			if($account->num_fails) {
				DAO_Pop3Account::updatePop3Account($account->id, array(
					DAO_Pop3Account::NUM_FAILS => 0,
				));
			}
			
			imap_expunge($mailbox);
			imap_close($mailbox);
			@imap_errors();
			 
			$logger->info("[POP3] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
		}
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		$timeout = ini_get('max_execution_time');
		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		$tpl->display('devblocks:cerberusweb.core::cron/pop3/config.tpl');
	}

	function saveConfigurationAction() {

		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};

class StorageCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$runtime = microtime(true);
		
		$logger->info("[Storage] Starting...");

		$max_runtime = time() + 30; // [TODO] Make configurable
		
		// Synchronize storage schemas (active+archive)
		$storage_schemas = DevblocksPlatform::getExtensions('devblocks.storage.schema', true);
		foreach($storage_schemas as $schema) { /* @var $schema Extension_DevblocksStorageSchema */
			if($max_runtime > time())
				$schema->unarchive($max_runtime);
			if($max_runtime > time())
				$schema->archive($max_runtime);
		}
		
		$logger->info("[Storage] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();

//		$timeout = ini_get('max_execution_time');
//		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		//$tpl->display('devblocks:cerberusweb.core::cron/storage/config.tpl');
	}

	function saveConfigurationAction() {
//		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
//		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};

class MailQueueCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
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
				foreach($messages as $message) { /* @var $message Model_MailQueue */
					$last_id = $message->id;
					
					if(!$message->send()) {
						$logger->error(sprintf("[Mail Queue] Failed sending message %d", $message->id));
						DAO_MailQueue::update($message->id, array(
							DAO_MailQueue::QUEUE_FAILS => min($message->queue_fails+1,255),
							DAO_MailQueue::QUEUE_DELIVERY_DATE => time() + 900, // retry in 15 min
						));
					} else {
						$logger->info(sprintf("[Mail Queue] Sent message %d", $message->id));
					}
				}
			}
		} while(!empty($messages) && $stop_time > time());
		
		$logger->info("[Mail Queue] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
		//$tpl = DevblocksPlatform::getTemplateService();
		//$tpl->display('devblocks:cerberusweb.core::cron/mail_queue/config.tpl');
	}
};

class Cron_VirtualAttendantScheduledBehavior extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog('Virtual Attendant Scheduler');
		$runtime = microtime(true);

		$stop_time = time() + 20; // [TODO] Make configurable

		$logger->info("Starting...");
		$last_behavior_id = 0;

		do {
			$behaviors = DAO_ContextScheduledBehavior::getWhere(
				sprintf("%s < %d AND %s > %d",
					DAO_ContextScheduledBehavior::RUN_DATE,
					time(),
					DAO_ContextScheduledBehavior::ID,
					$last_behavior_id
				),
				array(DAO_ContextScheduledBehavior::RUN_DATE),
				array(true),
				25
			);

			if(!empty($behaviors)) {
				foreach($behaviors as $behavior) {
					/* @var $behavior Model_ContextScheduledBehavior */
					try {
						if(empty($behavior->context) || empty($behavior->context_id) || empty($behavior->behavior_id))
							throw new Exception("Incomplete macro.");
					
						// Load context
						if(null == ($context_ext = DevblocksPlatform::getExtension($behavior->context, true)))
							throw new Exception("Invalid context.");
					
						// ACL: Ensure access to the context object
						//if(!$context_ext->authorize($context_id, $active_worker))
						//	throw new Exception("Access denied to context.");
							
						// Load macro
						if(null == ($macro = DAO_TriggerEvent::get($behavior->behavior_id))) /* @var $macro Model_TriggerEvent */
							throw new Exception("Invalid macro.");
						
						if($macro->is_disabled)
							throw new Exception("Macro disabled.");
							
						// ACL: Ensure the worker owns the macro
						//if(false == ($macro->owner_context == CerberusContexts::CONTEXT_WORKER && $macro->owner_context_id == $active_worker->id))
						//	 new Exception("Access denied to macro.");
					
						// Load event manifest
						if(null == ($ext = DevblocksPlatform::getExtension($macro->event_point, false))) /* @var $ext DevblocksExtensionManifest */
							throw new Exception("Invalid event.");

						// Execute
						$behavior->run();

						// Log
						$logger->info(sprintf("Executed behavior %d", $behavior->id));
						
					} catch (Exception $e) {
						$logger->error(sprintf("Failed executing behavior %d: %s", $behavior->id, $e->getMessage()));

						DAO_ContextScheduledBehavior::delete($behavior->id);
					}
					
					$last_behavior_id = $behavior->id;
				}
			}
			
		} while(!empty($behaviors) && $stop_time > time());

		$logger->info("Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function configure($instance) {
	}
};

class SearchCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$runtime = microtime(true);
		
		$logger->info("[Search] Starting...");
		
		// Loop through search schemas and batch index by ID or timestamp
		
		$schemas = DevblocksPlatform::getExtensions('devblocks.search.schema', true, true);

		$stop_time = time() + 30; // [TODO] Make configurable
		
		foreach($schemas as $schema) {
			if($stop_time > time())
				$schema->index($stop_time);
		}
		
		$logger->info("[Search] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}
};

class Cron_CalendarRecurringEventScheduler extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$runtime = microtime(true);
		
		$logger->info("[Calendar Recurring] Starting...");

		// [TODO] Cache
		$recurring_events = DAO_CalendarRecurringProfile::getWhere();

		// Run through every calendar recurring profile
		foreach($recurring_events as $recurring) { /* @var $recurring Model_CalendarRecurringProfile */
			//var_dump($recurring->date_start);
			// [TODO] We need to stop when the limit is reached too (not end date)
			$recurring->createRecurringEvents(strtotime("tomorrow", $recurring->date_start));
		}
		
		$logger->info("[Calendar Recurring] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}
	
	function configure($instance) {
	}
};