<?php
class ParseCron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @ini_set('memory_limit','64M');

        $timeout = ini_get('max_execution_time');
        echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
        echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';

        $runtime = microtime(true);
        	
//        echo 'Init time: ',((microtime(true)-$runtime)*1000),' ms<br>';

        echo "<BR>";
        flush();

        $total = 50;
        
        $mailfiles = scandir(APP_MAIL_PATH);
        
	    foreach($mailfiles as $file) {
	        $full_filename = APP_MAIL_PATH . $file;

	        if($file == '.' || $file == '..' || $file == '.svn')
	            continue;
	        
	        if (is_dir($full_filename)) {
	            $subfiles = scandir($full_filename);

	            foreach($subfiles as $subfile) {
	                $sub_filename = $full_filename . DIRECTORY_SEPARATOR . $subfile;
	                
	                if($subfile == '.' || $subfile == '..' || is_dir($sub_filename))
	                    continue;
	                
			        $this->_parseFile($sub_filename);
			        if(--$total <= 0) break;
	            }
	            
	        } else { // file
		        $this->_parseFile($full_filename);
	        }
	        
            if(--$total <= 0) break;
	    }

        echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
    }
    
    function _parseFile($full_filename) {
        $params = array();
        $params['include_bodies']	= true;
        $params['decode_bodies']	= true;
        $params['decode_headers']	= true;
        $params['crlf']				= "\r\n";
        
        echo "Reading ",$full_filename,"...<br>";
        
	    $fp = fopen($full_filename,'r');
        if(!$fp) return; // [TODO] Move this to fails later

           $params['input'] = '';
           $content =& $params['input'];
        
           while(!feof($fp)) {
               $content .= fgets($fp,4096);
           }
        
           echo "Decoding... "; flush();
           $time = microtime(true);
           $msg = Mail_mimeDecode::decode($params);
           $time = microtime(true) - $time;
           unset($params['input']);
           unset($content);
           echo "decoded! (",sprintf("%d",($time*1000))," ms)<br>"; flush();

           echo "Parsing... ";flush();
           $time = microtime(true);
           CerberusParser::parseMessage($msg);
           $time = microtime(true) - $time;
           echo "parsed! (",sprintf("%d",($time*1000))," ms)<br>";

           // [TODO] If we didn't have any errors
        @fclose($fp);
        unlink($full_filename);
        // [TODO] If we did have errors, move it to the fails directory

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

?>