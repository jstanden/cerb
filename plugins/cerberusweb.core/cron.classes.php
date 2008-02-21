<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2007, WebGroup Media LLC
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
class ParseCron extends CerberusCronPageExtension {
    function scanDirMessages($dir) {
        if(substr($dir,-1,1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
        $files = glob($dir . '*.msg');
        return $files;
    }
    
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @ini_set('memory_limit','64M');

        $timeout = ini_get('max_execution_time');
        echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ," secs<br>\r\n";
        echo 'Memory Limit: ', ini_get('memory_limit') ,"<br>\r\n";

        $runtime = microtime(true);
        	
        echo "<BR>\r\n";
        flush();

		$total = $this->getParam('max_messages', 500);
        
        $mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
	    $subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
	    $subdirs[] = $mailDir; // Add our root directory last

	    foreach($subdirs as $subdir) {
	    	if(!is_writable($subdir)) {
	    	    echo 'Write permission error, unable parse messages inside: '. $subdir. "...skipping<br>\r\n";
	    	    continue;
	    	}
	    	
	        $files = $this->scanDirMessages($subdir);
	        
	        foreach($files as $file) {
	            $filePart = basename($file);
                $parseFile = APP_MAIL_PATH . 'fail' . DIRECTORY_SEPARATOR . $filePart;
                rename($file, $parseFile);
		        $this->_parseFile($parseFile);
				flush();
		        if(--$total <= 0) break;
			}
			if($total <= 0) break;
	    }
	    
	    unset($files);
	    unset($subdirs);
	    
        echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>\r\n";
    }
    
    function _parseFile($full_filename) {
        $fileparts = pathinfo($full_filename);
        echo "Reading ",$fileparts['basename'],"...<br>\r\n";
        
        echo "Decoding... "; //flush();
        $time = microtime(true);
        
        $mime = mailparse_msg_parse_file($full_filename);
		$message = CerberusParser::parseMime($mime, $full_filename);
				
        $time = microtime(true) - $time;
        echo "decoded! (",sprintf("%d",($time*1000))," ms)<br>\r\n"; //flush();
		
//	    echo "<b>Plaintext:</b> ", $message->body,"<BR>";
//	    echo "<BR>";
//	    echo "<b>HTML:</b> ", htmlentities($message->htmlbody), "<BR>";
//	    echo "<BR>";
//	    echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
//	    echo "<HR>";
        
        echo "Parsing... ";//flush();
        $time = microtime(true);
        $ticket_id = CerberusParser::parseMessage($message);
        $time = microtime(true) - $time;
        echo "parsed! (",sprintf("%d",($time*1000))," ms) (Ticket ID: ",$ticket_id,")<br>\r\n";

        @unlink($full_filename);
        mailparse_msg_free($mime);

		echo "<hr>";
		flush();
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
		
        $tpl->assign('max_messages', $this->getParam('max_messages', 500));
		
		$tpl->display($tpl_path . 'cron/parser/config.tpl.php');
    }
    
	function saveConfigurationAction() {
		@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);
	}
};

// [TODO] Clear idle temp files (fileatime())
class MaintCron extends CerberusCronPageExtension {
    function run() {
        @ini_set('memory_limit','64M');
        
        $db = DevblocksPlatform::getDatabaseService();

        // Purge Deleted Content
        $purged = 0;
        $max_purges = 2500; // max per maint run // [TODO] Make this configurable from job
        
        $purge_waitdays = intval($this->getParam('purge_waitdays', 7));
        $purge_waitsecs = $purge_waitdays*24*60*60;
        
        do {	    
		    list($tickets, $null) = DAO_Ticket::search(
		    	array(),
		    	array(
		            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',1),
		            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_UPDATED_DATE,DevblocksSearchCriteria::OPER_LT,time()-$purge_waitsecs)
		        ),
		        250,
		        0,
		        SearchFields_Ticket::TICKET_LAST_WROTE,
		        0,
		        false
		    );
	
		    if(!empty($tickets)) {
			    $purged += count($tickets);
			    DAO_Ticket::delete(array_keys($tickets));
		    }
		    
        } while(!empty($tickets) && ($purged < $max_purges));
	    
        echo "Deleted ", $purged, " tickets!<br>\r\n";
        
        // Nuke orphaned words from the Bayes index
	    // [TODO] Make this configurable from job
	    $sql = "DELETE FROM bayes_words WHERE nonspam + spam < 2"; // only 1 occurrence
	    $db->Execute($sql);
        
	    // [TODO] After deletion, check for any leftover NULL rows and delete them

	    // [mdf] Remove any empty directories inside storage/mail/new
        $mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
	    $subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
    	foreach($subdirs as $subdir) {
    		$directory_empty = count(glob($subdir. DIRECTORY_SEPARATOR . '*')) === 0;
    		if($directory_empty && is_writeable($subdir)) {
    			rmdir($subdir);
    		}
	    }
	    
		// Recover any tickets assigned to a NULL bucket
		$sql = "SELECT DISTINCT t.category_id as id ".
			"FROM ticket t ".
			"LEFT JOIN category c ON (t.category_id=c.id) ".
			"WHERE c.id IS NULL AND t.category_id > 0";
		$rs = $db->Execute($sql);
		
		while(!$rs->EOF) {
			$sql = sprintf("UPDATE ticket SET category_id = 0 WHERE category_id = %d",
				$rs->fields['id']
			);
			$db->Execute($sql);
			$rs->MoveNext();
		}

	    
	    // Optimize/Vaccuum
	    // [TODO] Make this configurable from job
	    $perf = NewPerfMonitor($db); 
//	    $perf->optimizeDatabase();
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->assign('purge_waitdays', $this->getParam('purge_waitdays', 7));
		
		$tpl->display($tpl_path . 'cron/maint/config.tpl.php');
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
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->display($tpl_path . 'cron/heartbeat/config.tpl.php');
    }
};

class Pop3Cron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @set_time_limit(0); // Unlimited (if possible)
        @ini_set('memory_limit','64M');

        $accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */

        $timeout = ini_get('max_execution_time');
		$max_downloads = $this->getParam('max_messages', (($timeout) ? 20 : 50));
        
        // [JAS]: Make sure our output directory is writeable
	    if(!is_writable(APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR)) {
	        echo "The mail storage directory is not writeable.  Skipping POP3 download.<br>\r\n";
	        return;
	    }
        
        foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
            if(!$account->enabled)
                continue;
            
            echo 'Account being parsed is ', $account->nickname, "<br>\r\n";
            echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ," secs<br>\r\n";
            echo 'Memory Limit: ', ini_get('memory_limit') ,"<br>\r\n";
            	
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
             
            $mailbox = imap_open($connect,
            !empty($account->username)?$account->username:"",
            !empty($account->password)?$account->password:"")
            or die("Failed with error: ".imap_last_error());
            	
            $messages = array();
            $check = imap_check($mailbox);
            	
            // [TODO] Make this an account setting?
            $total = min($max_downloads,$check->Nmsgs);
            	
            echo 'Init time: ',((microtime(true)-$runtime)*1000)," ms<br>\r\n";

            echo "<BR>\r\n";
            flush();

            $runtime = microtime(true);

            for($i=1;$i<=$total;$i++) {
                /*
                * [TODO] Logic for max message size (>1MB, etc.) handling.  If over a
                * threshold then use the attachment parser (imap_fetchstructure) to toss
                * non-plaintext until the message fits.
                */
                 
                $msgno = $i;
                echo "<b>Downloading message ",$msgno,"</b> ";
                flush();
                 
                $time = microtime(true);
                 
                $headers = imap_fetchheader($mailbox, $msgno);
                $body = imap_body($mailbox, $msgno);

                do {
                	$unique = sprintf("%s.%04d",
						time(),
	        			rand(0,9999)
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
                echo "(",sprintf("%d",($time*1000))," ms)<br>\r\n";
                flush();
                imap_delete($mailbox, $msgno);
                continue;
            }
            	
            imap_expunge($mailbox);
            imap_close($mailbox);
            imap_errors();
            	
            echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>\r\n";
        }
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$timeout = ini_get('max_execution_time');
		$tpl->assign('max_messages', $this->getParam('max_messages', (($timeout) ? 20 : 50)));
		
		$tpl->display($tpl_path . 'cron/pop3/config.tpl.php');
    }
    
    function saveConfigurationAction() {

    	@$max_messages = DevblocksPlatform::importGPC($_POST['max_messages'],'integer');
		$this->setParam('max_messages', $max_messages);

		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
    }
};

class ParserFile {
    public $tmpname = null;
    public $mime_type = '';
    public $file_size = 0;
    
    public function setTempFile($tmpname,$mimetype='application/octet-stream') {
        $this->mime_type = $mimetype;
        
        if(!empty($tmpname) && file_exists($tmpname)) {
            $this->tmpname = $tmpname;
        }
    }
    
    public function getTempFile() {
        return $this->tmpname;
    }
    
    static public function makeTempFilename() {
        $path = DEVBLOCKS_PATH . 'tmp' . DIRECTORY_SEPARATOR;
        return tempnam($path,'mime');
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
    
}

?>