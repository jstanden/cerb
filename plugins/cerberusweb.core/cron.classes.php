<?php
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
        echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
        echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';

        $runtime = microtime(true);
        	
        echo "<BR>";
        flush();

        $total = 500;
        
        $mailDir = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR;
	    $subdirs = glob($mailDir . '*', GLOB_ONLYDIR);
	    $subdirs[] = $mailDir; // Add our root directory last

	    foreach($subdirs as $subdir) {
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
	    
        echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
    }
    
    function _parseFile($full_filename) {
        $fileparts = pathinfo($full_filename);
        echo "Reading ",$fileparts['basename'],"...<br>";
        
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
//		    echo "PART $st...<br>";

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
	                $tmpname = ParserFile::makeTempFilename($tmpname);
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
        echo "decoded! (",sprintf("%d",($time*1000))," ms)<br>"; //flush();
		
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
        echo "parsed! (",sprintf("%d",($time*1000))," ms) (Ticket ID: ",$ticket_id,")<br>";

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
	    
        echo "Deleted ", $purged, " tickets!<br>";
        
        // Nuke orphaned words from the Bayes index
	    // [TODO] Make this configurable from job
	    $sql = "DELETE FROM bayes_words WHERE nonspam + spam < 2"; // only 1 occurrence
	    $db->Execute($sql);
        
	    // [TODO] After deletion, check for any leftover NULL rows and delete them

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