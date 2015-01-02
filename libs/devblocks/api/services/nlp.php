<?php
class _DevblocksNaturalLanguageManager {
	static $instance = null;
	
	private function __construct(){
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksNaturalLanguageManager();
		}
		
		return self::$instance;
	}
	
	function parseTextWithPattern($text, $pattern) {
		$pattern_tokens = explode(' ', $pattern);
		$words = explode(' ', $text);
		$results = array();
		
		$regexp = $this->_makeRegexpFromPattern($pattern);
		
		preg_match($regexp, $pattern, $pattern_matches);
		preg_match($regexp, $text, $text_matches);

		// If our pattern doesn't match the given text, abort
		if(count($pattern_matches) != count($text_matches))
			return false;
		
		unset($pattern_matches[0]);
		unset($text_matches[0]);
		
		foreach($pattern_matches as $idx => $a) {
			$b = $text_matches[$idx];
			
			if(0 == strcasecmp($a, $b)) {
				// Ignore literal text
				
			} else {
				preg_match_all('#\[.*?\]#', $a, $placeholders);
				
				if(empty($placeholders))
					continue;
				
				$placeholders = $placeholders[0];
				$keys_when = array_keys($placeholders, '[when]');
				$keys_what = array_keys($placeholders, '[what]');
				
				if(!empty($keys_when) && !isset($results['when']))
					$results['when'] = array();
					
				if(!empty($keys_what) && !isset($results['what']))
					$results['what'] = array();
				
				$temporal_fragments = $this->_extractAllTemporalText($b);

				foreach(array_reverse($keys_when) as $key_when) {
					if(false == ($fragment = array_pop($temporal_fragments))) {
						return false;
					}
					
					$results['when'][] = $this->_normalizeTemporalText($fragment);
					
					$b = preg_replace('/' . preg_quote($fragment) . '/', '[when]', $b, 1);
				}
				
				foreach($keys_what as $key_what) {
					$b_diff = implode(' ', array_diff_assoc(explode(' ', $b), explode(' ', $a)));
					
					if(false == ($fragment = $this->_extractArbitraryText($b_diff))) {
						return false;
					}
					
					$results['what'][] = $fragment;
					
					$b = preg_replace('/' . preg_quote($fragment) . '/', '[what]', $b, 1);
				}			
			}
			
		}
		
		return $results;
	}
	
	private function _findTemporalAnchor($words) {
		foreach($words as $idx => $word) {
			if($this->_isTemporalWord($idx, $words, true))
				return $idx;
		}
		
		return false;
	}
	
	// [TODO] $strict could be replace with TYPES (time suffix, day names, etc)
	private function _isTemporalWord($idx, $words, $strict=false) {
		$word = strtolower($words[$idx]);
		
		// Remove punctuation
		$word = trim($word, ',.!?"\'');
		
		// [TODO] Month names, years
		
		// 00:00, 02:30p, 17:45, 6:45am
		if(in_array($word, array('yesterday','today','now', 'tomorrow'))) {
			return true;
		} elseif(in_array($word, array('mon', 'tue', 'tues', 'wed', 'weds', 'thu', 'thurs', 'fri', 'sat', 'sun'))) {
			// [TODO] Normalize the day names to full length
			return true;
		} elseif(in_array($word, array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'))) {
			return true;
		} elseif(in_array($word, array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec'))) {
			return true;
		} elseif(in_array($word, array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'))) {
			return true;
		} elseif(in_array($word, array(
			'sec', 'secs', 'second', 'seconds',
			'min', 'mins', 'minute', 'minutes',
			'hr', 'hrs', 'hour', 'hours',
			'day', 'days',
			'week', 'weeks',
			'mo', 'mos', 'month', 'months',
			'yr', 'year', 'years'
			))) {
			return true;
		} elseif(preg_match('#\d{1,2}(st|nd|rd|th)?#i', $word)) {
			return true;
		} elseif(preg_match('#\d{1,2}\:\d{2}(am|pm|a|p)?#i', $word)) {
			return true;
		// 1pm, 12a 
		} elseif(preg_match('#\d{1,2}(am|pm|a|p)?#i', $word)) {
			return true;
		} elseif(preg_match('#\+\d+(m|h|d|w)?#i', $word)) {
			return true;
		}

		// If we only care about the strict words above, then we stop
		
		if($strict)
			return false;
		
		// Now we can check less strict temporal words (like 'on' or 'until')
		
		if(in_array($word, array('morning', 'afternoon', 'evening', 'night', 'tonight')))
			return true;

		// Check bigrams
		
		if($idx + 1 < count($words)) {
			$next_word = strtolower($words[$idx+1]);
		
			// e.g. 15 mins, +2 days
			if(is_numeric(ltrim($word, '+-')) && $this->_isTemporalWord($idx+1, $words, true))
				return true;
		
			if(
				// is a prefix word
				in_array($word, array('a', 'an', 'at', 'in', 'from', 'of', 'on', 'last', 'next', 'this', 'to', 'until'))
				// followed by a strong temporal word
				&& $this->_isTemporalWord($idx+1, $words, true)
				) {
				return true;
			}
		}
		
		return false;
	}
	
	private function _normalizeTemporalText($text) {
		$text = preg_replace("/[^A-Z0-9\: ]/i", '', $text);
		
		$words = explode(' ', $text);
		
		// Only keep strong temporal words
		foreach($words as $idx => $word) {
			if(!$this->_isTemporalWord($idx, $words, true))
				unset($words[$idx]);
		}
		
		return implode(' ', $words);
	}
	
	private function _extractAllTemporalText($text) {
		$fragments = array();

		while($fragment = $this->_extractTemporalText($text)) {
			$fragments[] = $fragment;
			$text = preg_replace('/' . preg_quote($fragment) . '/', '[when]', $text, 1);
		}
		
		return $fragments;
	}
	
	private function _extractTemporalText($text) {
		$words = explode(' ', $text);
		$fragment = array();
		
		// [TODO] Kill punctuation? (keep +1, etc)
		
		// [TODO] Seek until we find a high value word, then move outward from that in both directions
		
		// [TODO] Check if these are time based words
		
		// Boolean not false (0 is permitted)
		if(false === ($anchor = $this->_findTemporalAnchor($words))) {
			return false;
		}
		
		$fragment[] = $words[$anchor];
		
		// Find the left boundary from the anchor

		for($idx=$anchor-1; $idx >= 0; $idx--) {
			$word = $words[$idx];
			
			if(!$this->_isTemporalWord($idx, $words, false))
				break;
			
			array_unshift($fragment, $word);
		}
		
		// Find the right boundary from the anchor
		
		for($idx=$anchor+1; isset($words[$idx]); $idx++) {
			$word = $words[$idx];
			
			if(!$this->_isTemporalWord($idx, $words, false))
				break;
			
			array_push($fragment, $word);
		}
		
		return implode(' ', $fragment);
	}
	
	private function _extractArbitraryText($text) {
		$words = explode(' ', $text);
		$fragment = array();
		
		foreach($words as $word) {
			if($this->_isPlaceholder($word))
				break;
			
			$fragment[] = $word;
		}
		
		return implode(' ', $fragment);		
	}
	
	private function _makeRegexpFromPattern($pattern) {
		$pattern_tokens = explode(' ', $pattern);
		$regexp = array();
		
		foreach($pattern_tokens as $token) {
			if($this->_isPlaceholder($token)) {
				if(end($regexp) != '(.*)')
					$regexp[] = "(.*)";
					
			} else {
				$regexp[] = '(' . preg_quote($token) . ')';
				
			}
		}
		
		return '#^' . implode(' ', $regexp) . '$#i';
	}
	
	private function _isPlaceholder($token) {
		if(preg_match('#^\[(.*)\]$#', $token))
			return true;
		
		return false;
	}	
};
