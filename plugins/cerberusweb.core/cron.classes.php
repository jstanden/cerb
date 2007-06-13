<?php
class ParseCron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @ini_set('memory_limit','8M');

        $timeout = ini_get('max_execution_time');
        echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
        echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';

        $runtime = microtime(true);
        	
        echo "<BR>";
        flush();

        $total = 25;
        
        // [TODO] This is inefficient, let's go back to opendir
	    $dir = opendir(APP_MAIL_PATH);
	    
	    while($file = readdir($dir)) {
	        $full_filename = APP_MAIL_PATH . $file;

	        if($file == '.' || $file == '..' || $file == '.svn')
	            continue;
	        
	        if (is_dir($full_filename)) {
	            $subdir = opendir($full_filename);

	            while($subfile = readdir($subdir)) {
	                $sub_filename = $full_filename . DIRECTORY_SEPARATOR . $subfile;
	                
	                if($subfile == '.' || $subfile == '..' || is_dir($sub_filename))
	                    continue;

			        $this->_parseFile($sub_filename);
			        if(--$total <= 0) break;
	            }
	            
	            @closedir($subdir);
	            
	        } else { // file
		        $this->_parseFile($full_filename);
		        if(--$total <= 0) break;
	        }
            
	    }
	    
	    @closedir($dir);

        echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
    }
    
    function _parseFile($full_filename) {
        echo "Reading ",$full_filename,"...<br>";
        
        echo "Decoding... "; flush();
        $time = microtime(true);
        
        $mail = mailparse_msg_parse_file($full_filename);
		$struct = mailparse_msg_get_structure($mail);
		$msginfo = mailparse_msg_get_part_data($mail);
		
		$message = new CerberusParserMessage();
		$message->headers = $msginfo['headers'];
		
		foreach($struct as $st) {
//		    echo "PART $st...<br>";

		    $section = mailparse_msg_get_part($mail, $st);
		    $info = mailparse_msg_get_part_data($section);
		    
		    // Attachment?
		    if(!empty($info['content-name'])) {
		        switch($info['content-disposition']) {
		            case 'inline':
		            case 'attachment':
					    $attach = new ParseCronFileBuffer($section, $info, $full_filename);
					    $message->files[$info['content-name']] = $attach;
		                break;
		                
		            default: // default?
		                break;
		        }
			    
		    } else { // no content name
		        if($info['content-type'] == 'text/plain') {
		            ob_start();
		            mailparse_msg_extract_part_file($section, $full_filename);
		            $message->body .= ob_get_contents();
		            ob_end_clean();

		        } elseif($info['content-type'] == 'text/html') {
		            ob_start();
		            mailparse_msg_extract_part_file($section, $full_filename);
		            $message->htmlbody .= ob_get_contents();
		            ob_end_clean();
		            // [TODO] Add the html part as an attachment		            

		        } else { } // some non text/html unnamed part
		    }
		    
		}
		
        $time = microtime(true) - $time;
        echo "decoded! (",sprintf("%d",($time*1000))," ms)<br>"; flush();
		
//	    echo "<b>Plaintext:</b> ", $message->body,"<BR>";
//	    echo "<BR>";
//	    echo "<b>HTML:</b> ", htmlentities($message->htmlbody), "<BR>";
//	    echo "<BR>";
//	    echo "<b>Files:</b> "; print_r($message->files); echo "<BR>";
//	    echo "<HR>";
        
	    mailparse_msg_free($mail);

        echo "Parsing... ";flush();
        $time = microtime(true);
        CerberusParser::parseMessage($message);
        $time = microtime(true) - $time;
        echo "parsed! (",sprintf("%d",($time*1000))," ms)<br>";

        // [TODO] If we did have errors, move it to the fails directory
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

class MaintCron extends CerberusCronPageExtension {
    function run() {
        @ini_set('memory_limit','64M');
        
        $purged = 0;

        do {	    
		    list($tickets, $tickets_count) = DAO_Ticket::search(array(
		            new DevblocksSearchCriteria(SearchFields_Ticket::TICKET_DELETED,'=',1)
		        ),
		        250,
		        0,
		        SearchFields_Ticket::TICKET_LAST_WROTE,
		        0,
		        true
		    );
	
		    $purged += count($tickets);
		    
		    DAO_Ticket::delete(array_keys($tickets));
        } while($tickets_count);
	    
        echo "Deleted ", $purged, " tickets!<br>";
        
	    // [TODO] After deletion, check for any leftover NULL rows and delete them
	    // [TODO] Optimize/Vaccuum?
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->display($tpl_path . 'cron/maint/config.tpl.php');
    }
};

class ParseCronFileBuffer {
    private $mime_filename = '';
    private $section = null;
    private $info = array();
    private $fp = null;
    public $tmpname = null;
    
    function __construct($section, $info, $mime_filename) {
        $this->mime_filename = $mime_filename;
        $this->section = $section;
        $this->info = $info;
        
        $path = DEVBLOCKS_PATH . 'tmp' . DIRECTORY_SEPARATOR;
        $this->tmpname = tempnam($path,'mime');
        $this->fp = fopen($this->tmpname,'wb');
        
        if($this->fp) {
            mailparse_msg_extract_part_file($this->section, $this->mime_filename, array($this, "writeCallback"));
        }
        
        @fclose($this->fp);
    }
    
    function writeCallback($chunk) {
        fwrite($this->fp, $chunk);
//        echo $chunk;
    }
}

?>