<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2002-2019, Webgroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerb.ai/license
|
| By using this software, you acknowledge having read this license
| and agree to be bound thereby.
| ______________________________________________________________________
|	http://cerb.ai	    http://webgroup.media
***********************************************************************/
/*
 * IMPORTANT LICENSING NOTE from your friends at Cerb
 *
 * Sure, it would be really easy to just cheat and edit this file to use
 * Cerb without paying for a license.  We trust you anyway.
 *
 * It takes a significant amount of time and money to develop, maintain,
 * and support high-quality enterprise software with a dedicated team.
 * For Cerb's entire history we've avoided taking money from outside
 * investors, and instead we've relied on actual sales from satisfied
 * customers to keep the project running.
 *
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * As a legitimate license owner, your feedback will help steer the project.
 * We'll also prioritize your issues, and work closely with you to make sure
 * your teams' needs are being met.
 *
 * - Jeff Standen and Dan Hildebrandt
 *	 Founders at Webgroup Media LLC; Developers of Cerb
 */
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');

class Exception_CerbInstaller extends Exception {}

/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 */
class CerberusInstaller {
	
	/**
	 * @param ... [TODO]
	 * @return string 'config', 'tmp' or FALSE
	 */
	public static function saveFrameworkConfig($db_driver, $db_engine, $encoding, $db_server, $db_name, $db_user, $db_pass) {
		$buffer = array();
		@$fp_in = fopen(APP_PATH . "/framework.config.php","r");
		
		if(!$fp_in) return FALSE;
		
		@$client_ip = (string) DevblocksPlatform::getClientIp();
		
		while(!feof($fp_in)) {
			$line = fgets($fp_in);
			$token = null;
			$value = null;
			
			// Check for particular define lines to rewrite
			if(preg_match('/^define\([\'\"](.*?)[\'\"].*?\,.*?[\'\"](.*?)[\'\"]\).*?$/i', $line, $matches)) {
				$token = $matches[1];
				
				switch(mb_strtoupper($token)) {
					case "APP_DB_ENGINE":
						$value = $db_engine;
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
					case "LANG_CHARSET_CODE":
						$value = (0==strcasecmp($encoding,'latin1')) ? 'iso-8859-1' : 'utf-8';
						break;
					case "DB_CHARSET_CODE":
						$value = (0==strcasecmp($encoding,'latin1')) ? 'latin1' : 'utf8';
						break;
					case "AUTHORIZED_IPS_DEFAULTS":
						$value = $client_ip;
						break;
				}
				
				if(!empty($token) && !empty($value)) {
					$line = sprintf("define('%s','%s');",$token, self::escape($value));
				}
			}
			
			$buffer[] = str_replace(array("\r","\n"),'',$line); // strip CRLF
		}
		
		if(is_resource($fp_in))
			fclose($fp_in);
		
		$saved = FALSE;
		
		// [JAS]: First try to just write back to the config file directly
		if(is_writeable(APP_PATH . "/framework.config.php")
			&& false !== (@$fp_out = fopen(APP_PATH . "/framework.config.php","w"))) {
			
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
	
	private static function escape($string) {
		$from = array("'",'"');
		$to = array("\\'",'\"');
		
		return str_replace($from, $to, $string);
	}
}
?>