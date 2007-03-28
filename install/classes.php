<?php
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');

/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 */
class CerberusInstaller {
	
	/**
	 * @param ... [TODO]
	 * @return string 'config', 'tmp' or FALSE
	 */
	public static function saveFrameworkConfig($db_driver, $db_server, $db_name, $db_user, $db_pass) {
		$buffer = array();
		@$fp_in = fopen(APP_PATH . "/framework.config.php","r");
		
		if(!$fp_in) return FALSE;
		
		while(!feof($fp_in)) {
			$line = fgets($fp_in);
			$token = null;
			$value = null;
			
			// Check for particular define lines to rewrite
			if(preg_match('/^define\([\'\"](.*?)[\'\"].*?\,.*?[\'\"](.*?)[\'\"]\).*?$/i', $line, $matches)) {
				$token = $matches[1];
				
				switch(strtoupper($token)) {
					case "APP_DB_DRIVER":
						$value = $db_driver;
						break;
					case "APP_DB_HOST":
						$value = $db_server;
						break;
					case "APP_DB_DATABASE":
						$value = $db_name;
						break;
					case "APP_DB_USER":
						$value = $db_user;
						break;
					case "APP_DB_PASS":
						$value = $db_pass;
						break;
				}
				
				if(!empty($token) && !empty($value)) {
					$line = sprintf("define('%s','%s');",$token, self::escape($value));
				}
			}
			
//			echo "LINE: ",$line,"<BR>";
			$buffer[] = str_replace(array("\r","\n"),'',$line); // strip CRLF			
		}
		
		@fclose($fp_in);
		
		$saved = FALSE;
		
		// [JAS]: First try to just write back to the config file directly
		if(is_writeable(APP_PATH . "/framework.config.php")) {
			@$fp_out = fopen(APP_PATH . "/framework.config.php","w");
			if(empty($fp_out)) break;
			
			if(is_array($buffer)) {
				$lines = count($buffer);
				for($x=0;$x<$lines;$x++) {
					$line = $buffer[$x];
					fwrite($fp_out,$line,strlen($line));
					if($x+1 != $lines)
						fwrite($fp_out,"\n",1);
				}
			}
			
			@fclose($fp_out);
			$saved = "config";
		}
		
		if(empty($saved)) {
			$saved = implode("\n", $buffer);
		}
		
		return $saved;
	}
	
	// [TODO] Move to patcher service
	public static function isDatabaseEmpty() {
		$db = DevblocksPlatform::getDatabaseService();
		
		$tables = $db->MetaTables('TABLE',true);
		
		return empty($tables);
	}
	
	private static function escape($string) {
		$from = array("'",'"');
		$to = array("\\'",'\"');
		
		return str_replace($from, $to, $string);
	}
}
?>