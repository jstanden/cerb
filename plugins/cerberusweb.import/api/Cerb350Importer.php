<?php
require_once(dirname(__FILE__) . '/Extension.php');

class ChCerb350Importer extends CerberusImporterExtension {
	function __construct($manifest) {
	    parent::__construct($manifest);
	}
  
	function configure() {
//		$tpl = DevblocksPlatform::getTemplateService();
//		$tpl->cache_lifetime = "0";
//		$tpl_path = dirname(__FILE__) . '/templates/';
//		$tpl->assign('path', $tpl_path);
//        
//		$tpl->display($tpl_path . 'cron/import/config.tpl.php');
	}
	
	function saveConfiguration() {
	    
	}
	
	function import() {
	    $start=1; 
	    $limit=10;

	    $db_host = $this->getParam('db_host', 'xev.webgroupmedia.com');
	    $db_name = $this->getParam('db_name', 'cer_wgm_support_3');
	    $db_user = $this->getParam('db_user', 'importer');
	    $db_pass = $this->getParam('db_pass', '2718.77');
	    
		define('HELPDESK_EMAIL', 'support@webgroupmedia.com');
		define('COPY_ATTACHMENTS', true);
		define('MAGIC_QUOTES', false); // [TODO]
	    define('RUN_MODE', 'web');
		
	    define('OUTPUT_DIR', APP_PATH . '/storage/mail/new/');
		define('DIR_LOAD_LIMIT', 2000); // How many per dir

		@ini_set('display_errors','On');
		@error_reporting(E_ALL ^ E_NOTICE);
		@ini_set('max_execution_time', 0); // no time limit
		@ini_set('memory_limit', '128M');
		
		$crlf = "\n";
		
		$db = @mysql_connect($db_host, $db_user, $db_pass) or die("ERROR: Failed to connect to remote database.");
		@mysql_select_db($db_name, $db) or die("ERROR: Database not found.  Check the DB_NAME setting.");
		
		// [TODO] Cache for a couple mins?
		$sql = "SELECT max(thread_id) FROM thread";
		$res = mysql_query($sql, $db);
		
		$total = 0;
		if(mysql_num_rows($res) && $row = mysql_fetch_row($res)) {
		    $total = $row[0];
		}
		
		echo "Total: ",$total,"<BR>";
		
		// [TODO] If $start >= $total we're done for now
		
		$sql = sprintf("SELECT t.ticket_id, t.ticket_mask, t.ticket_subject, t.is_closed, t.is_waiting_on_customer, ".
		    "UNIX_TIMESTAMP(t.ticket_date) as ticket_date, th.thread_id, th.thread_message_id, a.address_address as sender_address, ".
			"t.ticket_spam_trained, t.min_thread_id, t.max_thread_id, q.queue_reply_to, q.queue_id, q.queue_name ".
		    "FROM thread th ".
		    "INNER JOIN ticket t ON (th.ticket_id=t.ticket_id) ".
		    "INNER JOIN address a ON (th.thread_address_id=a.address_id) ".
		    "INNER JOIN queue q ON (t.ticket_queue_id=q.queue_id) ".
		    "WHERE t.is_deleted = 0 ".
		    "AND th.thread_id >= %d ".
		    "ORDER BY th.thread_id ASC ".
		    "LIMIT 0,%d ",
		        $start,
		        $limit
			);
		$res = mysql_query($sql, $db);
		
		$thread_id = 0;
		$CUR_DIR_LOAD = 0;

		// Determine the last bucket for this run
		$dir = opendir(OUTPUT_DIR);
		$CUR_DIR = null;
		while($file = readdir($dir)) {
		    if($file == '.' || $file == '..' || $file == '.svn' || $file == 'CVS' || !is_dir($file)) continue;
		    $CUR_DIR = $file;
		}
		@closedir($dir);
		
		// First time
		if(empty($CUR_DIR)) {
		    $CUR_DIR = sprintf("%07d", 1);
		    mkdir(OUTPUT_DIR . $CUR_DIR, 0664);
		}
		
		// Count dir files
		$dir = opendir(OUTPUT_DIR . $CUR_DIR);
		while($file = readdir($dir)) {
		    if($file == '.' || $file == '..') continue;
		    $CUR_DIR_LOAD++;
		}
		@closedir($dir);
		
    	if($res && mysql_num_rows($res))
		while($row = mysql_fetch_array($res)) {
		    $thread_id = intval($row['thread_id']); 
		    
		    // Get message content in order
		    $text = '';
		    $sql = sprintf("SELECT tc.thread_content_part ".
		        "FROM thread_content_part tc ".
		        "WHERE tc.thread_id = %d ".
		        "ORDER BY tc.content_id ASC ",
		            $thread_id
		    );
		    $res2 = mysql_query($sql);

		    // concat the 255 char chunks of content
		    if($res2 && mysql_num_rows($res2)) {
		        while($row2 = mysql_fetch_row($res2)) {
		            $text .= $row2[0];
		        }
		        unset($row2);
		        mysql_free_result($res2);
		    }
		    
			// [HEADERS]
			$reply_to = trim($row['queue_reply_to']);
			
			$hdrs = array(
			           'To'		 => !empty($reply_to) ? $reply_to : HELPDESK_EMAIL,
			           'From'    => $row['sender_address'],
			           'Subject' => $row['ticket_subject']
		           );
		           
		    $hdrs['Message-Id'] = !empty($row['thread_message_id']) ? $row['thread_message_id'] : '';
		    
		    $mask = !empty($row['ticket_mask']) ? $row['ticket_mask'] : 'CERB3-' . $row['ticket_id'];
		    
		    if(intval($thread_id)==intval($row['min_thread_id'])) { // Opening thread
			    $hdrs['X-CerberusNew'] = 1;
		        $hdrs['X-CerberusMask'] = $mask;
			    $hdrs['X-CerberusQueue'] = $row['queue_name'];
			    $hdrs['X-CerberusStatus'] = (!empty($row['is_closed'])) ? 'C' : ((!empty($row['is_waiting_on_customer'])) ? 'W' : 'O');
			    $hdrs['X-CerberusCreatedDate'] = $row['ticket_date'];
			    $hdrs['X-CerberusSpamTraining'] = (empty($row['ticket_spam_trained'])) ? '' : (($row['ticket_spam_trained']==1) ? 'S' : 'N');
			    
		    } else { // Reply thread
		        $hdrs['X-CerberusAppendTo'] = $mask;
		    }
			
		    // [MIME PARTS]
			$mime = new Mail_mime($crlf);
			
			$mime->setTXTBody($text);
		//	$mime->setHTMLBody($html);
		
		    // [TODO] Attachments?
			if(COPY_ATTACHMENTS) {
			    $sql = sprintf("SELECT ta.file_id, ta.file_name ".
			        "FROM thread_attachments ta ".
			        "WHERE ta.thread_id = %d ".
			        "ORDER BY ta.file_id ASC ",
			        $thread_id
			    );
			    $rs_files = mysql_query($sql, $db);
			    
			    if($rs_files && mysql_num_rows($rs_files))
			    while($row_files = mysql_fetch_row($rs_files)) {
			        $attach_content = '';
			        $sql = sprintf("SELECT tap.part_content ".
			            "FROM thread_attachments_parts tap ".
			            "WHERE tap.file_id = %d ".
			            "ORDER BY tap.part_id ASC",
			            $row_files[0]
			        );
			        $rs_content = mysql_query($sql, $db);
			        
			        if($rs_content && mysql_num_rows($rs_content)) {
				        while($row_content = mysql_fetch_row($rs_content)) {
				            $attach_content .= $row_content[0];
				        }
				        unset($row_content);
				        mysql_free_result($rs_content);
			        }
			        
			        // Sanitize file names
			        $parts = split("[\\\/]", $row_files[1]);
			        
			        $attach_file = DEVBLOCKS_PATH . 'tmp/'.array_pop($parts);
			        
			        $fp_attach = fopen($attach_file,"w");
			        if(!$fp_attach) continue;
			        fwrite($fp_attach, $attach_content);
			        @fclose($fp_attach);
			
			        $mime->addAttachment($attach_file, 'application/octet-stream', $row_files[1]);
			        unlink($attach_file);
			    }
			} // end attachments
		    
		    // [BUILD / OUTPUT]
		
			$body = $mime->get();
			$hdrs = $mime->headers($hdrs);
		    
			if($CUR_DIR_LOAD >= DIR_LOAD_LIMIT) {
			    // [TODO] Make the next directory
			    do {
			        $CUR_DIR = sprintf("%07d", intval($CUR_DIR) + 1);
			    } while(file_exists(OUTPUT_DIR . $CUR_DIR));
			    
			    mkdir(OUTPUT_DIR . $CUR_DIR, 0664) or die("Couldn't make new output storage directory.");
			    
			    $CUR_DIR_LOAD = 0;
			}
			
		    $fp = fopen(OUTPUT_DIR . sprintf('%s/msg%07d.msg',
		        $CUR_DIR,
		        $thread_id
		    ),'w');
		    
		    if(!$fp) die("Can't open output file.");
		    
		    foreach($hdrs as $k => $v) {
		        fwrite($fp, $k.': '.$v.$crlf);
		    }
		    fwrite($fp, $crlf);
		    fwrite($fp, $body);
		    
		    @fclose($fp);
		    
		    $CUR_DIR_LOAD++;
		    
		    unset($hdrs);
		    unset($body);
		    unset($mime);
		    
		    echo sprintf("Wrote message %d<br>\r\n",
		        $thread_id
		    );
		    flush();
		}
		
	    mysql_close($db);	
	}
  
};

// [TODO] Verify the proper C3 database format/version
// [TODO] Stripslashes on output if remote DB has magic_quotes_gpc
