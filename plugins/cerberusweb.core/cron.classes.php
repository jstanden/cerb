<?php
class ParseCron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @set_time_limit(0); // Unlimited (if possible)
        @ini_set('memory_limit','64M');

        $params = array();
        $params['include_bodies']	= true;
        $params['decode_bodies']	= true;
        $params['decode_headers']	= true;
        $params['crlf']				= "\r\n";

        $timeout = ini_get('max_execution_time');
        echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
        echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';

        $runtime = microtime(true);
        	
        echo 'Init time: ',((microtime(true)-$runtime)*1000),' ms<br>';

        echo "<BR>";
        flush();

        $total = 25;
        
	    if ($handle = opendir(APP_MAIL_PATH)) {
	    while ($total && false !== ($file = readdir($handle))) {
	        $full_filename = APP_MAIL_PATH . $file;
	        if (is_dir($full_filename))
	            continue;
	        
	        echo "Reading ",$file,"...<br>";
	        
	        $fp = fopen($full_filename,'r');
	        if(!$fp) continue; // [TODO] Move this to fails later

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
            
            $total--;
	    }
	    closedir($handle);

        $runtime = microtime(true);

        echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
        }
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$tpl->display($tpl_path . 'cron/parser/config.tpl.php');
    }
};

?>