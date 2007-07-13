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

        $total = 500;
        
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
        
        $mail = mailparse_msg_parse_file($full_filename);
		$struct = mailparse_msg_get_structure($mail);
		$msginfo = mailparse_msg_get_part_data($mail);
		
		$message = new CerberusParserMessage();
		$message->headers = $msginfo['headers'];
		
		$settings = CerberusSettings::getInstance();
		$is_attachments_enabled = $settings->get(CerberusSettings::ATTACHMENTS_ENABLED,1);
		$attachments_max_size = $settings->get(CerberusSettings::ATTACHMENTS_MAX_SIZE,10);
		
		foreach($struct as $st) {
//		    echo "PART $st...<br>\r\n";

		    $section = mailparse_msg_get_part($mail, $st);
		    $info = mailparse_msg_get_part_data($section);
		    
		    // Attachment?
		    if(!empty($info['content-name'])) {
		        switch($info['content-disposition']) {
		            case 'inline':
		            case 'attachment':
		                if(!$is_attachments_enabled) {
		                    break; // skip attachment
		                }
					    $attach = new ParseCronFileBuffer($section, $info, $full_filename);
		                
					    // [TODO] This could be more efficient by not even saving in the first place above:
	                    // Make sure our attachment is under the max preferred size
					    if(filesize($attach->tmpname) > ($attachments_max_size * 1024000)) {
					        @unlink($attach->tmpname);
					        break;
					    }
					    
					    $message->files[$info['content-name']] = $attach;
					    
		                break;
		                
		            default: // default?
		                break;
		        }
			    
		    } else { // no content name
		        if($info['content-type'] == 'text/plain') {
		            ob_start();
		            @mailparse_msg_extract_part_file($section, $full_filename);
		            @$message->body .= ob_get_contents();
		            ob_end_clean();

		        } elseif($info['content-type'] == 'text/html') {
		            ob_start();
		            @mailparse_msg_extract_part_file($section, $full_filename);
		            @$message->htmlbody .= ob_get_contents();
		            ob_end_clean();
		            
		            // [TODO] Add the html part as an attachment
	                $tmpname = ParserFile::makeTempFilename();
	                $html_attach = new ParserFile();
	                $html_attach->setTempFile($tmpname,'text/html');
	                @file_put_contents($tmpname,$message->htmlbody);
	                $html_attach->file_size = filesize($tmpname);
	                $message->files["original_message.html"] = $html_attach;
	                unset($html_attach);

		        } else { } // some non text/html unnamed part
		    }
		    
		}
		
        $time = microtime(true) - $time;
        echo "decoded! (",sprintf("%d",($time*1000))," ms)<br>\r\n"; //flush();
		
//	    echo "<b>Plaintext:</b> ", $message->body,"<BR>";
//	    echo "<BR>";
//	    echo "<b>HTML:</b> ", htmlentities($message->htmlbody), "<BR>";
//	    echo "<BR>";
//	    echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
//	    echo "<HR>";
        
	    mailparse_msg_free($mail);

        echo "Parsing... ";//flush();
        $time = microtime(true);
        $ticket_id = CerberusParser::parseMessage($message);
        $time = microtime(true) - $time;
        echo "parsed! (",sprintf("%d",($time*1000))," ms) (Ticket ID: ",$ticket_id,")<br>\r\n";

        unlink($full_filename);

		echo "<hr>";
		flush();
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->display($tpl_path . 'cron/parser/config.tpl.php');
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
        
        do {	    
		    list($tickets, $null) = DAO_Ticket::search(array(
		            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',1)
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
        
		$tpl->display($tpl_path . 'cron/maint/config.tpl.php');
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