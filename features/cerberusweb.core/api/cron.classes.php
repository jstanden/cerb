<?php
/***********************************************************************
 | Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
 |-----------------------------------------------------------------------
 | All source code & content (c) Copyright 2010, WebGroup Media LLC
 |   unless specifically noted otherwise.
 |
 | This source code is released under the Cerberus Public License.
 | The latest version of this license can be found here:
 | http://www.cerberusweb.com/license.php
 |
 | By using this software, you acknowledge having read this license
 | and agree to be bound thereby.
 | ______________________________________________________________________
 |	http://www.cerberusweb.com	  http://www.webgroupmedia.com/
 ***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends on the Cerberus Helpdesk Team
 *
 * Sure, it would be so easy to just cheat and edit this file to use the
 * software without paying for it.  But we trust you anyway.  In fact, we're
 * writing this software for you!
 *
 * Quality software backed by a dedicated team takes money to develop.  We
 * don't want to be out of the office bagging groceries when you call up
 * needing a helping hand.  We'd rather spend our free time coding your
 * feature requests than mowing the neighbors' lawns for rent money.
 *
 * We've never believed in encoding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your in-box that you probably
 * haven't had since spammers found you in a game of "E-mail Address
 * Battleship".  Miss. Miss. You sunk my in-box!
 *
 * A legitimate license entitles you to support, access to the developer
 * mailing list, the ability to participate in betas and the warm fuzzy
 * feeling of feeding a couple obsessed developers who want to help you get
 * more done than 'the other guy'.
 *
 * - Jeff Standen, Mike Fogg, Brenan Cavish, Darren Sugita, Dan Hildebrandt
 * 		and Joe Geck.
 *   WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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

		foreach($subdirs as $subdir) {
			if(!is_writable($subdir)) {
				$logger->error('[Parser] Write permission error, unable parse messages inside: '. $subdir. "...skipping");
				continue;
			}

			$files = $this->scanDirMessages($subdir);
			 
			foreach($files as $file) {
				$filePart = basename($file);
				$parseFile = APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $filePart;
				rename($file, $parseFile);
				$this->_parseFile($parseFile);
				//				flush();
				if(--$total <= 0) break;
			}
			if($total <= 0) break;
		}
	  
		unset($files);
		unset($subdirs);
	  
		$logger->info("[Parser] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
	}

	function _parseFile($full_filename) {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$fileparts = pathinfo($full_filename);
		$logger->info("[Parser] Reading ".$fileparts['basename']."...");

		$time = microtime(true);

		$mime = mailparse_msg_parse_file($full_filename);
		$message = CerberusParser::parseMime($mime, $full_filename);

		$time = microtime(true) - $time;
		$logger->info("[Parser] Decoded! (".sprintf("%d",($time*1000))." ms)");

		//	    echo "<b>Plaintext:</b> ", $message->body,"<BR>";
		//	    echo "<BR>";
		//	    echo "<b>HTML:</b> ", htmlspecialchars($message->htmlbody), "<BR>";
		//	    echo "<BR>";
		//	    echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
		//	    echo "<HR>";

		$time = microtime(true);
		$ticket_id = CerberusParser::parseMessage($message);
		$time = microtime(true) - $time;
		
		$logger->info("[Parser] Parsed! (".sprintf("%d",($time*1000))." ms) " .
			(!empty($ticket_id) ? ("(Ticket ID: ".$ticket_id.")") : ("(Local Delivery Rejected.)")));

		@unlink($full_filename);
		mailparse_msg_free($mime);

		//		flush();
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->assign('max_messages', $this->getParam('max_messages', 500));

		$tpl->display($tpl_path . 'cron/parser/config.tpl');
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

		// Purge Deleted Content
		$purge_waitdays = intval($this->getParam('purge_waitdays', 7));
		$purge_waitsecs = time() - (intval($purge_waitdays) * 86400);

		$sql = sprintf("DELETE QUICK FROM ticket ".
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));

		$tpl->display($tpl_path . 'cron/maint/config.tpl');
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/heartbeat/config.tpl');
	}
};

class ImportCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		
		$logger->info("[Importer] Starting Import Task");
		
		@set_time_limit(0); // Unlimited (if possible)
		 
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
		 		return $this->_handleImportContact($xml);
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
				// Hash by team ID and bucket name
				$hash = md5($bucket->team_id . strtolower($bucket->name));
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
			$iDestGroupId = DAO_Group::createTeam(array(
				DAO_Group::TEAM_NAME => $sGroup,				
			));
			
			// Give all superusers manager access to this new group
			if(is_array($workers))
			foreach($workers as $worker) {
				if($worker->is_superuser)
					DAO_Group::setTeamMember($iDestGroupId,$worker->id,true);
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
		$iLastWorkerId = 0;
		if($iNumMessages > 1) {
			if(null != (@$iLastWorkerId = $email_to_worker_id[strtolower($lastWroteInst->email)])) {
				$sLastActionCode = CerberusTicketActionCode::TICKET_WORKER_REPLY;
			} else {
				$sLastActionCode = CerberusTicketActionCode::TICKET_CUSTOMER_REPLY;
				$iLastWorkerId = 0;
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
			DAO_Ticket::CREATED_DATE => $iCreatedDate,
			DAO_Ticket::UPDATED_DATE => $iUpdatedDate,
			DAO_Ticket::TEAM_ID => intval($iDestGroupId),
			DAO_Ticket::CATEGORY_ID => intval($iDestBucketId),
			DAO_Ticket::LAST_ACTION_CODE => $sLastActionCode,
			DAO_Ticket::LAST_WORKER_ID => intval($iLastWorkerId),
		);
		$ticket_id = DAO_Ticket::createTicket($fields);

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
						DAO_Attachment::MESSAGE_ID => $email_id,
						DAO_Attachment::DISPLAY_NAME => $sFileName,
						DAO_Attachment::MIME_TYPE => $sMimeType,
					);
					$file_id = DAO_Attachment::create($fields);
					
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
				DAO_TicketComment::TICKET_ID => intval($ticket_id),
				DAO_TicketComment::CREATED => intval($iCommentDate),
				DAO_TicketComment::ADDRESS_ID => intval($commentAuthorInst->id),
				DAO_TicketComment::COMMENT => $sCommentText,
			);
			$comment_id = DAO_TicketComment::create($fields);
			
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
		if(null != ($worker_id = DAO_Worker::lookupAgentEmail($sEmail))) {
			$logger->info('[Importer] Avoiding creating duplicate worker #'.$worker_id.' ('.$sEmail.')');
			return true;
		}
		
		$fields = array(
			DAO_Worker::EMAIL => $sEmail,
			DAO_Worker::PASSWORD => $sPassword, // pre-MD5'd
			DAO_Worker::FIRST_NAME => $sFirstName,
			DAO_Worker::LAST_NAME => $sLastName,
			DAO_Worker::IS_SUPERUSER => intval($isSuperuser),
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

	private function _handleImportContact($xml) {
		$settings = DevblocksPlatform::getPluginSettingsService();
		$logger = DevblocksPlatform::getConsoleLog();

		$sFirstName = (string) $xml->first_name;
		$sLastName = (string) $xml->last_name;
		$sEmail = (string) $xml->email;
		$sPassword = (string) $xml->password;
		$sOrganization = (string) $xml->organization;
		
		// Dupe check org
		if(null != ($address = DAO_Address::lookupAddress($sEmail))) {
			$logger->info('[Importer] Avoiding creating duplicate contact #'.$address->id.' ('.$sEmail.')');
			// [TODO] Still associate with org if local blank?
			// [TODO] Still associate password if local blank?
			return true;
		}
		
		$fields = array(
			DAO_Address::FIRST_NAME => $sFirstName,
			DAO_Address::LAST_NAME => $sLastName,
			DAO_Address::EMAIL => $sEmail,
		);

		// Associate SC password
		if(!empty($sPassword) && $sPassword != md5('')) {
			$fields[DAO_Address::IS_REGISTERED] = 1;
			$fields[DAO_Address::PASS] = $sPassword;
		}
		
		$address_id = DAO_Address::create($fields);
		
		// Associate with organization
		if(!empty($sOrganization)) {
			if(null != ($org_id = DAO_ContactOrg::lookup($sOrganization, true))) {
				DAO_Address::update($address_id, array(
					DAO_Address::CONTACT_ORG_ID => $org_id
				));
			}
		}
		
		$logger->info('[Importer] Imported contact #'.$address_id.' ('.$sEmail.')');
		
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
				DAO_TicketComment::CREATED => $created,
				DAO_TicketComment::TICKET_ID => $ticket->id,
				DAO_TicketComment::COMMENT => $note,
				DAO_TicketComment::ADDRESS_ID => $author_address->id,
			);
			
			if(null !== ($comment_id = DAO_TicketComment::create($fields)))
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/import/config.tpl');
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
		
		@set_time_limit(0); // Unlimited (if possible)

		$accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts Model_Pop3Account[] */

		$timeout = ini_get('max_execution_time');
		
		// Allow runtime overloads (by host, etc.)
		@$gpc_pop3_max = DevblocksPlatform::importGPC($_REQUEST['pop3_max'],'integer');
		
		$max_downloads = !empty($gpc_pop3_max) ? $gpc_pop3_max : $this->getParam('max_messages', (($timeout) ? 20 : 50));
		
		// [JAS]: Make sure our output directory is writeable
		if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
			$logger->error("[POP3] The mail storage directory is not writeable.  Skipping POP3 download.");
			return;
		}

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
				continue;
			}
			 
			$messages = array();
			$check = imap_check($mailbox);
			 
			// [TODO] Make this an account setting?
			$total = min($max_downloads,$check->Nmsgs);
			 
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
				continue;
			}
			 
			imap_expunge($mailbox);
			imap_close($mailbox);
			imap_errors();
			 
			$logger->info("[POP3] Total Runtime: ".number_format((microtime(true)-$runtime)*1000,2)." ms");
		}
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$timeout = ini_get('max_execution_time');
		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		$tpl->display($tpl_path . 'cron/pop3/config.tpl');
	}

	function saveConfigurationAction() {

		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};

class ParseCronFileBuffer extends ParserFile {
	private $mime_filename = '';
	private $section = null;
	private $info = array();
	private $fp = null;

	function __construct($section, $info, $mime_filename) {
		$this->mime_filename = $mime_filename;
		$this->section = $section;
		$this->info = $info;

		$this->setTempFile(ParserFile::makeTempFilename(),@$info['content-type']);
		$this->fp = fopen($this->getTempFile(),'wb');

		if($this->fp && !empty($this->section) && !empty($this->mime_filename)) {
			mailparse_msg_extract_part_file($this->section, $this->mime_filename, array($this, "writeCallback"));
		}

		@fclose($this->fp);
	}

	function writeCallback($chunk) {
		$this->file_size += fwrite($this->fp, $chunk);
		//        echo $chunk;
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
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

//		$timeout = ini_get('max_execution_time');
//		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		//$tpl->display($tpl_path . 'cron/storage/config.tpl');
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
				sprintf("%s = %d AND %s > %d AND %s < %d",
					DAO_MailQueue::IS_QUEUED,
					1,
					DAO_MailQueue::ID,
					$last_id,
					DAO_MailQueue::QUEUE_FAILS,
					10
				),
				array(DAO_MailQueue::QUEUE_PRIORITY, DAO_MailQueue::UPDATED),
				array(false, true),
				25
			);
	
			if(!empty($messages)) {
				foreach($messages as $message) { /* @var $message Model_MailQueue */
					$last_id = $message->id;
					
					if(!$message->send()) {
						$logger->error(sprintf("[Mail Queue] Failed sending message %d", $message->id));
						DAO_MailQueue::update($message->id, array(
							DAO_MailQueue::QUEUE_FAILS => min($message->queue_fails+1,255),
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);

		$tpl->display($tpl_path . 'cron/mail_queue/config.tpl');
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
//		$timeout = ini_get('max_execution_time');
//		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));

		//$tpl->display($tpl_path . 'cron/storage/config.tpl');
	}
	
	function saveConfigurationAction() {
//		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
//		$this->setParam('max_messages', $max_messages);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
	}
};
