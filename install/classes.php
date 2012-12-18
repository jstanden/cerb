<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2012, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Devblocks Public License.
| The latest version of this license can be found here:
| http://cerberusweb.com/license
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
 * We've never believed in hiding our source code out of paranoia over not
 * getting paid.  We want you to have the full source code and be able to
 * make the tweaks your organization requires to get more done -- despite
 * having less of everything than you might need (time, people, money,
 * energy).  We shouldn't be your bottleneck.
 *
 * We've been building our expertise with this project since January 2002.  We
 * promise spending a couple bucks [Euro, Yuan, Rupees, Galactic Credits] to
 * let us take over your shared e-mail headache is a worthwhile investment.
 * It will give you a sense of control over your inbox that you probably
 * haven't had since spammers found you in a game of 'E-mail Battleship'.
 * Miss. Miss. You sunk my inbox!
 *
 * A legitimate license entitles you to support from the developers,
 * and the warm fuzzy feeling of feeding a couple of obsessed developers
 * who want to help you get more done.
 *
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
require_once(DEVBLOCKS_PATH . 'Devblocks.class.php');

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
	
	private static function escape($string) {
		$from = array("'",'"');
		$to = array("\\'",'\"');
		
		return str_replace($from, $to, $string);
	}
}
?>