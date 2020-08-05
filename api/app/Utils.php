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
class CerberusUtils {
	/**
	 *
	 * @param string $string
	 * @return array
	 */
	static function parseRfcAddressList($input) {
		$addys = [];
		
		if(!is_array($input))
			$input = array($input);
		
		foreach($input as $string) {
			$addy_rows = self::_parseRfcAddress($string);
			
			if(!empty($addy_rows))
				$addys = array_merge($addys, $addy_rows);
		}
		
		// If we failed to find any content, try a regexp parse
		if(empty($addys)) {
			foreach($input as $string) {
				if(preg_match('/[\\w\\.\\-+=*_]*@[\\w\\.\\-+=*_]*/', $string, $matches)) {
					if(is_array($matches))
					foreach($matches as $match) {
						$addy_rows = self::_parseRfcAddress($match);
						
						if(!empty($addy_rows))
							$addys = array_merge($addys, $addy_rows);
						
					}
				}
			}
		}

		@imap_errors();
		
		return $addys;
	}
	
	static private function _parseRfcAddress($string) {
		@$addy_rows = imap_rfc822_parse_adrlist($string, '');
		$results = [];
		
		if(is_array($addy_rows))
		foreach($addy_rows as $idx => $addy_row) {
			if(empty($addy_row->host))
				continue;
		
			if($addy_row->host == '.SYNTAX-ERROR.')
				continue;
			
			if(strlen($addy_row->mailbox) == 1 && preg_match('/[^a-zA-Z0-9]/', $addy_row->mailbox))
				continue;
			
			if(!Swift_Validate::email($addy_row->mailbox . '@' . $addy_row->host))
				continue;
			
			$results[] = $addy_row;
		}
		
		@imap_errors();
		
		return $results;
	}
	
}