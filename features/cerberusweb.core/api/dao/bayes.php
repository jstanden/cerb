<?php
/***********************************************************************
| Cerb(tm) developed by Webgroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, Webgroup Media LLC
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

/**
 * Bayesian Anti-Spam DAO
 */
class DAO_Bayes {
	private function DAO_Bayes() {}
	
	/**
	 * @return CerberusWord[]
	 */
	static function lookupWordIds($words) {
		$db = DevblocksPlatform::getDatabaseService();
		$tmp = array();
		$outwords = array(); // CerberusWord
		
		// Escaped set
		if(is_array($words))
		foreach($words as $word) {
			$tmp[] = addslashes($word);
		}
		
		if(empty($words))
			return array();
		
		$sql = sprintf("SELECT id,word,spam,nonspam FROM bayes_words WHERE word IN ('%s')",
			implode("','", $tmp)
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		// [JAS]: Keep a list of words we can check off as we index them with IDs
		$tmp = array_flip($words); // words are now keys
		
		// Existing Words
		
		while($row = mysql_fetch_assoc($rs)) {
			$w = new Model_BayesWord();
			$w->id = intval($row['id']);
			$w->word = mb_convert_case($row['word'], MB_CASE_LOWER);
			$w->spam = intval($row['spam']);
			$w->nonspam = intval($row['nonspam']);
			
			$outwords[mb_convert_case($w->word, MB_CASE_LOWER)] = $w;
			unset($tmp[$w->word]); // check off we've indexed this word
		}
		
		mysql_free_result($rs);
		
		// Insert new words
		if(is_array($tmp))
		foreach($tmp as $new_word => $v) {
			$sql = sprintf("INSERT INTO bayes_words (word) VALUES (%s)",
				$db->qstr($new_word)
			);
			$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
			$id = $db->LastInsertId();
			
			$w = new Model_BayesWord();
			$w->id = $id;
			$w->word = $new_word;
			$outwords[$w->word] = $w;
		}
		
		return $outwords;
	}
	
	/**
	 * @return array Two element array (keys: spam,nonspam)
	 */
	static function getStatistics() {
		$db = DevblocksPlatform::getDatabaseService();
		
		// [JAS]: [TODO] Change this into a 'replace' index?
		$sql = "SELECT spam, nonspam FROM bayes_stats";
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		
		if($row = mysql_fetch_assoc($rs)) {
			$spam = intval($row['spam']);
			$nonspam = intval($row['nonspam']);
		} else {
			$spam = 0;
			$nonspam = 0;
			$sql = "INSERT INTO bayes_stats (spam, nonspam) VALUES (0,0)";
			$db->Execute($sql);
		}
		
		return array('spam' => $spam,'nonspam' => $nonspam);
	}
	
	static function addOneToSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET spam = spam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	static function addOneToNonSpamTotal() {
		$db = DevblocksPlatform::getDatabaseService();
		$sql = "UPDATE bayes_stats SET nonspam = nonspam + 1";
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	static function addOneToSpamWord($word_ids=array()) {
		if(!is_array($word_ids)) $word_ids = array($word_ids);
		if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET spam = spam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
	
	static function addOneToNonSpamWord($word_ids=array()) {
		if(!is_array($word_ids)) $word_ids = array($word_ids);
		if(empty($word_ids)) return;
		$db = DevblocksPlatform::getDatabaseService();
		$sql = sprintf("UPDATE bayes_words SET nonspam = nonspam + 1 WHERE id IN(%s)", implode(',',$word_ids));
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
	}
};

class Model_BayesWord {
	public $id = -1;
	public $word = '';
	public $spam = 0;
	public $nonspam = 0;
	public $probability = CerberusBayes::PROBABILITY_UNKNOWN;
	public $interest_rating = 0.0;
};

class CerberusBayes {
	const PROBABILITY_CEILING = 0.9999;
	const PROBABILITY_FLOOR = 0.0001;
	const PROBABILITY_UNKNOWN = 0.4;
	const PROBABILITY_MEDIAN = 0.5;
	const MAX_INTERESTING_WORDS = 15;
	const MAX_BODY_LENGTH = 15000;
	
	/**
	 * @param string $text A string of text to break into unique words
	 * @param integer $min The minimum word length used
	 * @param integer $max The maximum word length used
	 * @return array An array with unique words as keys
	 */
	static function parseUniqueWords($text,$min=3,$max=24) {
// ** REFACTOR BEGIN
		// Encode apostrophes/etc
//		$tokens = array(
//			'__apos__' => '\''
//		);

		// [TODO] Implement this back in Bayes
		
		// URLs
		// Reference: http://daringfireball.net/2009/11/liberal_regex_for_matching_urls
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__url'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
		// IPs
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#(\d{1,3}\.){3}\d{1,3}#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__ip'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
		// Email addresses
//		$matches = array();
//		$count = 0;
//		if(preg_match_all("#\w+\@\w+\.\w+#", $text, $matches)) {
//			foreach($matches[0] as $match) {
//				$token = '__email'.$count++.'__';
//				$tokens[$token] = $match;
//			}
//		}
		
//		$text = str_replace(array_values($tokens), array_keys($tokens), $text);
		
// ** REFACTOR END
		
		//$string = preg_replace("/[^\p{Greek}\p{N}]/u", ' ', $string);
		
		// Force lowercase and strip non-word punctuation (a-z, 0-9, _)
		if(function_exists('mb_ereg_replace')) {
			$text = mb_ereg_replace("[^[:alnum:]]", ' ', mb_convert_case($text, MB_CASE_LOWER));
			
			// Sort unique words w/ condensed spaces
			$words = array_flip(explode(' ', mb_ereg_replace('\s+', ' ', $text)));
			
		} else {
			$text = preg_replace("/[^[:alnum:]]/u", ' ', mb_convert_case($text, MB_CASE_LOWER));
			
			// Sort unique words w/ condensed spaces
			$words = array_flip(explode(' ', preg_replace("/\\s+/", ' ', trim($text))));
		}

		// Toss words that are too common
		$words = self::_removeCommonWords($words);
		
		// Toss anything over/under the word length bounds
		foreach($words as $k => $v) {
			$len = mb_strlen($k);
			if($len < $min || $len > $max || is_numeric($k)) { // [TODO]: Make decision on !numeric?
				unset($words[$k]); // toss
			}
		}
		
		return $words;
	}
	
	static private function _removeCommonWords($words) {
		// English
		unset($words['a']);
		unset($words['a\'s']);
		unset($words['able']);
		unset($words['about']);
		unset($words['above']);
		unset($words['according']);
		unset($words['accordingly']);
		unset($words['across']);
		unset($words['actually']);
		unset($words['after']);
		unset($words['afterwards']);
		unset($words['again']);
		unset($words['against']);
		unset($words['ain\'t']);
		unset($words['all']);
		unset($words['allow']);
		unset($words['allows']);
		unset($words['almost']);
		unset($words['alone']);
		unset($words['along']);
		unset($words['already']);
		unset($words['also']);
		unset($words['although']);
		unset($words['always']);
		unset($words['am']);
		unset($words['among']);
		unset($words['amongst']);
		unset($words['an']);
		unset($words['and']);
		unset($words['another']);
		unset($words['any']);
		unset($words['anybody']);
		unset($words['anyhow']);
		unset($words['anyone']);
		unset($words['anything']);
		unset($words['anyway']);
		unset($words['anyways']);
		unset($words['anywhere']);
		unset($words['apart']);
		unset($words['appear']);
		unset($words['appreciate']);
		unset($words['appropriate']);
		unset($words['are']);
		unset($words['aren\'t']);
		unset($words['around']);
		unset($words['as']);
		unset($words['aside']);
		unset($words['ask']);
		unset($words['asking']);
		unset($words['associated']);
		unset($words['at']);
		unset($words['available']);
		unset($words['away']);
		unset($words['awfully']);
		unset($words['be']);
		unset($words['became']);
		unset($words['because']);
		unset($words['become']);
		unset($words['becomes']);
		unset($words['becoming']);
		unset($words['been']);
		unset($words['before']);
		unset($words['beforehand']);
		unset($words['behind']);
		unset($words['being']);
		unset($words['believe']);
		unset($words['below']);
		unset($words['beside']);
		unset($words['besides']);
		unset($words['best']);
		unset($words['better']);
		unset($words['between']);
		unset($words['beyond']);
		unset($words['both']);
		unset($words['brief']);
		unset($words['but']);
		unset($words['by']);
		unset($words['c\'mon']);
		unset($words['c\'s']);
		unset($words['came']);
		unset($words['can']);
		unset($words['can\'t']);
		unset($words['cannot']);
		unset($words['cant']);
		unset($words['cause']);
		unset($words['causes']);
		unset($words['certain']);
		unset($words['certainly']);
		unset($words['changes']);
		unset($words['clearly']);
		unset($words['co']);
		unset($words['com']);
		unset($words['come']);
		unset($words['comes']);
		unset($words['concerning']);
		unset($words['consequently']);
		unset($words['consider']);
		unset($words['considering']);
		unset($words['contain']);
		unset($words['containing']);
		unset($words['contains']);
		unset($words['corresponding']);
		unset($words['could']);
		unset($words['couldn\'t']);
		unset($words['course']);
		unset($words['currently']);
		unset($words['definitely']);
		unset($words['described']);
		unset($words['despite']);
		unset($words['did']);
		unset($words['didn\'t']);
		unset($words['different']);
		unset($words['do']);
		unset($words['does']);
		unset($words['doesn\'t']);
		unset($words['doing']);
		unset($words['don\'t']);
		unset($words['done']);
		unset($words['down']);
		unset($words['downwards']);
		unset($words['during']);
		unset($words['each']);
		unset($words['edu']);
		unset($words['eg']);
		unset($words['eight']);
		unset($words['either']);
		unset($words['else']);
		unset($words['elsewhere']);
		unset($words['enough']);
		unset($words['entirely']);
		unset($words['especially']);
		unset($words['et']);
		unset($words['etc']);
		unset($words['even']);
		unset($words['ever']);
		unset($words['every']);
		unset($words['everybody']);
		unset($words['everyone']);
		unset($words['everything']);
		unset($words['everywhere']);
		unset($words['ex']);
		unset($words['exactly']);
		unset($words['example']);
		unset($words['except']);
		unset($words['far']);
		unset($words['few']);
		unset($words['fifth']);
		unset($words['first']);
		unset($words['five']);
		unset($words['followed']);
		unset($words['following']);
		unset($words['follows']);
		unset($words['for']);
		unset($words['former']);
		unset($words['formerly']);
		unset($words['forth']);
		unset($words['four']);
		unset($words['from']);
		unset($words['further']);
		unset($words['furthermore']);
		unset($words['get']);
		unset($words['gets']);
		unset($words['getting']);
		unset($words['given']);
		unset($words['gives']);
		unset($words['go']);
		unset($words['goes']);
		unset($words['going']);
		unset($words['gone']);
		unset($words['got']);
		unset($words['gotten']);
		unset($words['greetings']);
		unset($words['had']);
		unset($words['hadn\'t']);
		unset($words['happens']);
		unset($words['hardly']);
		unset($words['has']);
		unset($words['hasn\'t']);
		unset($words['have']);
		unset($words['haven\'t']);
		unset($words['having']);
		unset($words['he']);
		unset($words['he\'s']);
		unset($words['hello']);
		unset($words['help']);
		unset($words['hence']);
		unset($words['her']);
		unset($words['here']);
		unset($words['here\'s']);
		unset($words['hereafter']);
		unset($words['hereby']);
		unset($words['herein']);
		unset($words['hereupon']);
		unset($words['hers']);
		unset($words['herself']);
		unset($words['hi']);
		unset($words['him']);
		unset($words['himself']);
		unset($words['his']);
		unset($words['hither']);
		unset($words['hopefully']);
		unset($words['how']);
		unset($words['howbeit']);
		unset($words['however']);
		unset($words['i']);
		unset($words['i\'d']);
		unset($words['i\'ll']);
		unset($words['i\'m']);
		unset($words['i\'ve']);
		unset($words['ie']);
		unset($words['if']);
		unset($words['ignored']);
		unset($words['immediate']);
		unset($words['in']);
		unset($words['inasmuch']);
		unset($words['inc']);
		unset($words['indeed']);
		unset($words['indicate']);
		unset($words['indicated']);
		unset($words['indicates']);
		unset($words['inner']);
		unset($words['insofar']);
		unset($words['instead']);
		unset($words['into']);
		unset($words['inward']);
		unset($words['is']);
		unset($words['isn\'t']);
		unset($words['it']);
		unset($words['it\'d']);
		unset($words['it\'ll']);
		unset($words['it\'s']);
		unset($words['its']);
		unset($words['itself']);
		unset($words['just']);
		unset($words['keep']);
		unset($words['keeps']);
		unset($words['kept']);
		unset($words['know']);
		unset($words['known']);
		unset($words['knows']);
		unset($words['last']);
		unset($words['lately']);
		unset($words['later']);
		unset($words['latter']);
		unset($words['latterly']);
		unset($words['least']);
		unset($words['less']);
		unset($words['lest']);
		unset($words['let']);
		unset($words['let\'s']);
		unset($words['like']);
		unset($words['liked']);
		unset($words['likely']);
		unset($words['little']);
		unset($words['look']);
		unset($words['looking']);
		unset($words['looks']);
		unset($words['ltd']);
		unset($words['mainly']);
		unset($words['many']);
		unset($words['may']);
		unset($words['maybe']);
		unset($words['me']);
		unset($words['mean']);
		unset($words['meanwhile']);
		unset($words['merely']);
		unset($words['might']);
		unset($words['more']);
		unset($words['moreover']);
		unset($words['most']);
		unset($words['mostly']);
		unset($words['much']);
		unset($words['must']);
		unset($words['my']);
		unset($words['myself']);
		unset($words['name']);
		unset($words['namely']);
		unset($words['nd']);
		unset($words['near']);
		unset($words['nearly']);
		unset($words['necessary']);
		unset($words['need']);
		unset($words['needs']);
		unset($words['neither']);
		unset($words['never']);
		unset($words['nevertheless']);
		unset($words['new']);
		unset($words['next']);
		unset($words['nine']);
		unset($words['no']);
		unset($words['nobody']);
		unset($words['non']);
		unset($words['none']);
		unset($words['noone']);
		unset($words['nor']);
		unset($words['normally']);
		unset($words['not']);
		unset($words['nothing']);
		unset($words['novel']);
		unset($words['now']);
		unset($words['nowhere']);
		unset($words['obviously']);
		unset($words['of']);
		unset($words['off']);
		unset($words['often']);
		unset($words['oh']);
		unset($words['ok']);
		unset($words['okay']);
		unset($words['old']);
		unset($words['on']);
		unset($words['once']);
		unset($words['one']);
		unset($words['ones']);
		unset($words['only']);
		unset($words['onto']);
		unset($words['or']);
		unset($words['other']);
		unset($words['others']);
		unset($words['otherwise']);
		unset($words['ought']);
		unset($words['our']);
		unset($words['ours']);
		unset($words['ourselves']);
		unset($words['out']);
		unset($words['outside']);
		unset($words['over']);
		unset($words['overall']);
		unset($words['own']);
		unset($words['particular']);
		unset($words['particularly']);
		unset($words['per']);
		unset($words['perhaps']);
		unset($words['placed']);
		unset($words['please']);
		unset($words['plus']);
		unset($words['possible']);
		unset($words['presumably']);
		unset($words['probably']);
		unset($words['provides']);
		unset($words['que']);
		unset($words['quite']);
		unset($words['qv']);
		unset($words['rather']);
		unset($words['rd']);
		unset($words['re']);
		unset($words['really']);
		unset($words['reasonably']);
		unset($words['regarding']);
		unset($words['regardless']);
		unset($words['regards']);
		unset($words['relatively']);
		unset($words['respectively']);
		unset($words['right']);
		unset($words['said']);
		unset($words['same']);
		unset($words['saw']);
		unset($words['say']);
		unset($words['saying']);
		unset($words['says']);
		unset($words['second']);
		unset($words['secondly']);
		unset($words['see']);
		unset($words['seeing']);
		unset($words['seem']);
		unset($words['seemed']);
		unset($words['seeming']);
		unset($words['seems']);
		unset($words['seen']);
		unset($words['self']);
		unset($words['selves']);
		unset($words['sensible']);
		unset($words['sent']);
		unset($words['serious']);
		unset($words['seriously']);
		unset($words['seven']);
		unset($words['several']);
		unset($words['shall']);
		unset($words['she']);
		unset($words['should']);
		unset($words['shouldn\'t']);
		unset($words['since']);
		unset($words['six']);
		unset($words['so']);
		unset($words['some']);
		unset($words['somebody']);
		unset($words['somehow']);
		unset($words['someone']);
		unset($words['something']);
		unset($words['sometime']);
		unset($words['sometimes']);
		unset($words['somewhat']);
		unset($words['somewhere']);
		unset($words['soon']);
		unset($words['sorry']);
		unset($words['specified']);
		unset($words['specify']);
		unset($words['specifying']);
		unset($words['still']);
		unset($words['sub']);
		unset($words['such']);
		unset($words['sup']);
		unset($words['sure']);
		unset($words['t\'s']);
		unset($words['take']);
		unset($words['taken']);
		unset($words['tell']);
		unset($words['tends']);
		unset($words['th']);
		unset($words['than']);
		unset($words['thank']);
		unset($words['thanks']);
		unset($words['thanx']);
		unset($words['that']);
		unset($words['that\'s']);
		unset($words['thats']);
		unset($words['the']);
		unset($words['their']);
		unset($words['theirs']);
		unset($words['them']);
		unset($words['themselves']);
		unset($words['then']);
		unset($words['thence']);
		unset($words['there']);
		unset($words['there\'s']);
		unset($words['thereafter']);
		unset($words['thereby']);
		unset($words['therefore']);
		unset($words['therein']);
		unset($words['theres']);
		unset($words['thereupon']);
		unset($words['these']);
		unset($words['they']);
		unset($words['they\'d']);
		unset($words['they\'ll']);
		unset($words['they\'re']);
		unset($words['they\'ve']);
		unset($words['think']);
		unset($words['third']);
		unset($words['this']);
		unset($words['thorough']);
		unset($words['thoroughly']);
		unset($words['those']);
		unset($words['though']);
		unset($words['three']);
		unset($words['through']);
		unset($words['throughout']);
		unset($words['thru']);
		unset($words['thus']);
		unset($words['to']);
		unset($words['together']);
		unset($words['too']);
		unset($words['took']);
		unset($words['toward']);
		unset($words['towards']);
		unset($words['tried']);
		unset($words['tries']);
		unset($words['truly']);
		unset($words['try']);
		unset($words['trying']);
		unset($words['twice']);
		unset($words['two']);
		unset($words['un']);
		unset($words['under']);
		unset($words['unfortunately']);
		unset($words['unless']);
		unset($words['unlikely']);
		unset($words['until']);
		unset($words['unto']);
		unset($words['up']);
		unset($words['upon']);
		unset($words['us']);
		unset($words['use']);
		unset($words['used']);
		unset($words['useful']);
		unset($words['uses']);
		unset($words['using']);
		unset($words['usually']);
		unset($words['value']);
		unset($words['various']);
		unset($words['very']);
		unset($words['via']);
		unset($words['viz']);
		unset($words['vs']);
		unset($words['want']);
		unset($words['wants']);
		unset($words['was']);
		unset($words['wasn\'t']);
		unset($words['way']);
		unset($words['we']);
		unset($words['we\'d']);
		unset($words['we\'ll']);
		unset($words['we\'re']);
		unset($words['we\'ve']);
		unset($words['welcome']);
		unset($words['well']);
		unset($words['went']);
		unset($words['were']);
		unset($words['weren\'t']);
		unset($words['what']);
		unset($words['what\'s']);
		unset($words['whatever']);
		unset($words['when']);
		unset($words['whence']);
		unset($words['whenever']);
		unset($words['where']);
		unset($words['where\'s']);
		unset($words['whereafter']);
		unset($words['whereas']);
		unset($words['whereby']);
		unset($words['wherein']);
		unset($words['whereupon']);
		unset($words['wherever']);
		unset($words['whether']);
		unset($words['which']);
		unset($words['while']);
		unset($words['whither']);
		unset($words['who']);
		unset($words['who\'s']);
		unset($words['whoever']);
		unset($words['whole']);
		unset($words['whom']);
		unset($words['whose']);
		unset($words['why']);
		unset($words['will']);
		unset($words['willing']);
		unset($words['wish']);
		unset($words['with']);
		unset($words['within']);
		unset($words['without']);
		unset($words['won\'t']);
		unset($words['wonder']);
		unset($words['would']);
		unset($words['wouldn\'t']);
		unset($words['yes']);
		unset($words['yet']);
		unset($words['you']);
		unset($words['you\'d']);
		unset($words['you\'ll']);
		unset($words['you\'re']);
		unset($words['you\'ve']);
		unset($words['your']);
		unset($words['yours']);
		unset($words['yourself']);
		unset($words['yourselves']);
		unset($words['zero']);
		
		
		return $words;
	}
	
	/**
	 * @param string $text A string of text to run through spam scoring
	 * @return array Analyzed statistics
	 */
	static function processText($text) {
		$text = DevblocksPlatform::strUnidecode($text);
		$words = self::parseUniqueWords($text);
		$words = self::_lookupWordIds($words);
		$words = self::_analyze($words);
		return $words;
	}
	
	static function markTicketAsSpam($ticket_id) {
		self::_markTicketAs($ticket_id, true);
	}
	
	static function markTicketAsNotSpam($ticket_id) {
		self::_markTicketAs($ticket_id, false);
	}
	
	// [TODO] Accept batch tickets for training for efficiencies sake
	static private function _markTicketAs($ticket_id,$spam=true) {
		// [TODO] Make sure we can't retrain tickets which are already spam trained
		// [TODO] This is a performance killer
		$ticket = DAO_Ticket::get($ticket_id);
		
		if($ticket->spam_training != CerberusTicketSpamTraining::BLANK)
			return TRUE;
			
		// pull up text of first ticket message
		// [TODO] This is a performance killer
		$first_message = DAO_Message::get($ticket->first_message_id);

		if(empty($first_message))
			return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = '';
		if(!empty($ticket->subject)) {
			// SplitCamelCapsSubjects
			$hits = preg_split("{(?<=[a-z])(?=[A-Z])}x", $ticket->subject);
			if(is_array($hits) && !empty($hits)) {
				$content .= implode(' ',$hits);
			}
		}
		$content .= ' ' . $first_message->getContent();
		
		if(strlen($content) > self::MAX_BODY_LENGTH)
			$content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));
		
		$words = self::processText($content);
		
		// Train interesting words as spam/notspam
//		$out = self::_calculateSpamProbability($words);
//		self::_trainWords($out['words'],$spam);
		self::_trainWords($words,$spam); // [TODO] Testing, train all words
		
		// Increase the bayes_stats spam or notspam total count by 1
		// [TODO] This is a performance killer (could be done in batches)
		if($spam) {
			DAO_Bayes::addOneToSpamTotal();
			DAO_Address::addOneToSpamTotal($ticket->first_wrote_address_id);
		} else {
			DAO_Bayes::addOneToNonSpamTotal();
			DAO_Address::addOneToNonSpamTotal($ticket->first_wrote_address_id);
		}
		
		// Forced training should leave a cache of 0.0001 or 0.9999 on the ticket table
		$fields = array(
			'spam_score' => ($spam) ? 0.9999 : 0.0001,
			'spam_training' => ($spam) ? CerberusTicketSpamTraining::SPAM : CerberusTicketSpamTraining::NOT_SPAM,
		);
		DAO_Ticket::update($ticket_id,$fields);

		return TRUE;
	}

	/**
	 * @param Model_BayesWord[] $words
	 * @param boolean $spam
	 */
	static private function _trainWords($words, $spam=true) {
		if(!is_array($words))
			return;
	
		$ids = array();
		foreach($words as $word) { /* @var $word Model_BayesWord */
			$ids[] = $word->id;
		}
			
		if($spam) {
			DAO_Bayes::addOneToSpamWord($ids);
		} else {
			DAO_Bayes::addOneToNonSpamWord($ids);
		}
		
		unset($ids);
	}
	
	/**
	 * @param array $words An array indexed with words to look up
	 */
	static private function _lookupWordIds($words) {
		$pos = 0;
		$batch_size = 100; //[TODO] Tune this
		$outwords = array(); //
				
		while(array() != ($batch = array_slice($words,$pos,$batch_size,true))) {
			$batch = array_keys($batch); // words are now values
			$word_ids = DAO_Bayes::lookupWordIds($batch);
			$outwords = array_merge($outwords, $word_ids);
			$pos += $batch_size;
		}
		
		return $outwords;
	}
	
	static private function _analyze($words) {
		foreach($words as $k => $w) {
			$words[$k]->probability = self::calculateWordProbability($w);
			
			// [JAS]: If a word appears more than 5 times (counting weight) in the corpus, use it.  Otherwise de-emphasize.
			if(($w->nonspam * 1) + $w->spam >= 5)
				$words[$k]->interest_rating = self::_getMedianDeviation($w->probability);
			else
				$words[$k]->interest_rating = 0.00;
		}
		
		return $words;
	}
	
	static public function _combineP($argv) {
		// [JAS]: Variable for all our probabilities multiplied, for Naive Bayes
		$AB = 1; // probabilities: A*B*C...
		$ZY = 1; // compliments: (1-A)*(1-B)*(1-C)...
		
		foreach($argv as $v) {
			$AB *= $v;
			$ZY *= (1-$v);
		}

		$combined_p = $AB / ($AB + $ZY);
		
		switch($combined_p)
		{
			case $combined_p > self::PROBABILITY_CEILING:
				return self::PROBABILITY_CEILING;
				break;
			case $combined_p < self::PROBABILITY_FLOOR:
				return self::PROBABILITY_FLOOR;
				break;
		}
		
		return number_format($combined_p,4);
	}
	
	/**
	 * @param float $p Probability
	 * @return float Median Deviation
	 */
	static private function _getMedianDeviation($p) {
		if($p > self::PROBABILITY_MEDIAN)
			return $p - self::PROBABILITY_MEDIAN;
		else
			return self::PROBABILITY_MEDIAN - $p;
	}
	
	/**
	 * @param Model_BayesWord $word
	 * @return float The probability of the word being spammy.
	 */
	static public function calculateWordProbability(Model_BayesWord $word) {
		static $stats = null; // [JAS]: [TODO] Keep an eye on this.
		if(is_null($stats)) $stats = DAO_Bayes::getStatistics();
		
		$ngood = max($stats['nonspam'],1);
		$nbad = max($stats['spam'],1);
		
		$g = intval($word->nonspam * 1);
		$b = intval($word->spam);

		// [JAS]: If less than 5 occurrences total
		if(($g*1 + $b) < 5) {
			$prob = self::PROBABILITY_UNKNOWN;
			
		} else {
			$prob = max(self::PROBABILITY_FLOOR,
				min(self::PROBABILITY_CEILING,
					floatval(
						min(1,($b/$nbad))
						/
						( $g/$ngood + $b/$nbad )
					)
				)
			);
		}
		
		return number_format($prob,4);
	}
	
	/**
	 * @param Model_BayesWord $a
	 * @param Model_BayesWord $b
	 */
	static private function _sortByInterest($a, $b) {
		if ($a->interest_rating == $b->interest_rating) {
			return 0;
		}
		return ($a->interest_rating < $b->interest_rating) ? -1 : 1;
	}
	
	/**
	 * @param Model_BayesWord[] $words
	 * @return array 'probability' = Overall Spam Probability, 'words' = interesting words
	 */
	static private function _calculateSpamProbability($words) {
		$probabilities = array();
		
		// Sort words by interest descending
		$interesting_words = $words;
		usort($interesting_words,array('CerberusBayes','_sortByInterest'));
		$interesting_words = array_slice($interesting_words,-1 * self::MAX_INTERESTING_WORDS);

		// Combine word probabilities into an overall probability
		foreach($interesting_words as $word) { /* @var $word Model_BayesWord */
			$probabilities[] = $word->probability;
		}
		$combined = self::_combineP($probabilities);
		
		return array('probability' => $combined, 'words' => $interesting_words);
	}
	
	static function calculateTicketSpamProbability($ticket_id, $readonly=false) {
		// pull up text of first ticket message
		$messages = DAO_Message::getMessagesByTicket($ticket_id);
		$first_message = array_shift($messages);
		$ticket = DAO_Ticket::get($ticket_id);
		
		if(empty($ticket) || empty($first_message) || !($first_message instanceOf Model_Message))
			return FALSE;
		
		// Pass text to analyze() to get back interesting words
		$content = '';
		if(!empty($ticket->subject)) {
			// SplitCamelCapsSubjects
			$hits = preg_split("{(?<=[a-z])(?=[A-Z])}x", $ticket->subject);
			if(is_array($hits) && !empty($hits)) {
				$content .= implode(' ',$hits);
			}
		}
		$content .= ' ' . $first_message->getContent();
		
		// Only check the first 15000 characters for spam, rounded to a sentence
		if(strlen($content) > self::MAX_BODY_LENGTH)
			$content = substr($content, 0, strrpos(substr($content, 0, self::MAX_BODY_LENGTH), ' '));
		
		$words = self::processText($content);
		$out = self::_calculateSpamProbability($words);

		// Make a word list
		$rawwords = array();
		foreach($out['words'] as $k=>$v) { /* @var $v Model_BayesWord */
			$rawwords[] = $v->word;
		}
		
		// Cache probability
		if(!$readonly) {
			$fields = array(
				DAO_Ticket::SPAM_SCORE => $out['probability'],
				DAO_Ticket::INTERESTING_WORDS => substr(implode(',',array_reverse($rawwords)),0,255),
			);
			DAO_Ticket::update($ticket_id, $fields);
		}
		
		return $out;
	}
};
