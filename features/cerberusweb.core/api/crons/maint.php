<?php
// [TODO] Clear idle temp files (fileatime())
class MaintCron extends CerberusCronPageExtension {
	function run() {
		$logger = DevblocksPlatform::services()->log();
		
		$logger->info("[Maint] Starting Maintenance Task");
		
		$db = DevblocksPlatform::services()->database();

		// Platform
		DAO_Platform::maint();
		
		// Purge expired sessions
		Cerb_DevblocksSessionHandler::gc(0);

		// Purge deleted records past the undo window
		$purge_wait_days = intval($this->getParam('purge_waitdays', 7));
		$purge_wait_before = time() - ($purge_wait_days * 86400);
		DAO_Ticket::deleteAfterUndoWait($purge_wait_before, 1_000);
		
		// Give plugins a chance to run maintenance (nuke NULL rows, etc.)
		$eventMgr = DevblocksPlatform::services()->event();
		$eventMgr->trigger(
			new Model_DevblocksEvent(
				'cron.maint',
				[]
			)
		);
		
		// Nuke orphaned words from the Bayes index
		// [TODO] Make this configurable from job
		$sql = "DELETE FROM bayes_words WHERE nonspam + spam < 2"; // only 1 occurrence
		$db->ExecuteMaster($sql);

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
		
		// Clean up /tmp/php* files if ctime > 12 hours ago
		
		$tmp_dir = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		$tmp_deletes = 0;
		$tmp_ctime_max = time() - (60*60*12);
		
		if(false !== ($php_tmpfiles = glob($tmp_dir . 'php*', GLOB_NOSORT))) {
			// If created more than 12 hours ago
			foreach($php_tmpfiles as $php_tmpfile) {
				if(filectime($php_tmpfile) < $tmp_ctime_max) {
					unlink($php_tmpfile);
					$tmp_deletes++;
				}
			}
			
			$logger->info(sprintf('[Maint] Cleaned up %d temporary PHP files.', $tmp_deletes));
		}
		
		// Clean up /tmp/mime* files if ctime > 12 hours ago
		
		$tmp_dir = APP_TEMP_PATH . DIRECTORY_SEPARATOR;
		$tmp_deletes = 0;
		$tmp_ctime_max = time() - (60*60*12);
		
		if(false !== ($php_tmpmimes = glob($tmp_dir . 'mime*', GLOB_NOSORT))) {
			foreach($php_tmpmimes as $php_tmpmime) {
				// If created more than 12 hours ago
				if(filectime($php_tmpmime) < $tmp_ctime_max) {
					unlink($php_tmpmime);
					$tmp_deletes++;
				}
			}
			
			$logger->info(sprintf('[Maint] Cleaned up %d temporary MIME files.', $tmp_deletes));
		}
		
	}

	function configure($instance) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));
		
		$tpl->display('devblocks:cerberusweb.core::cron/maint/config.tpl');
	}

	function saveConfiguration() {
		$purge_waitdays = DevblocksPlatform::importGPC($_POST['purge_waitdays'] ?? null, 'integer');
		$this->setParam('purge_waitdays', $purge_waitdays);
	}
};