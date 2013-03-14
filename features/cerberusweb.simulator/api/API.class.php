<?php
/***********************************************************************
| Cerb(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2013, WebGroup Media LLC
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
 * IMPORTANT LICENSING NOTE from your friends on the Cerb Development Team
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
 \* - Jeff Standen, Darren Sugita, Dan Hildebrandt
 *	 Webgroup Media LLC - Developers of Cerb
 */
/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 * @author Dan Hildebrandt <dan@webgroupmedia.com> [DDH]
 */
require_once(dirname(__FILE__) . '/Datasets.class.php');

class CerberusSimulator {
	private static $instance = null;
	
	private function __construct() {
	}
	
	public static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new CerberusSimulator();
		}
		return self::$instance;
	}
	
	public function generateEmails(SimulatorDataset $dataset,$how_many=10) {
		$emails = array();
		$generator = new CerberusSimulatorGenerator();
		
		for($x=0;$x<$how_many;$x++) {
			$email = $dataset->getRandomEmail();
			$email = $generator->generateSender($email); // [TODO] Clean up
			$emails[] = $email;
		}
		
		return $emails;
	}
	
};

class SimulatorDataset {
	protected $emails = array();
	protected $tokens = array();
	
	protected function addEmailTemplate($subject,$body,$sender=null) {
		$this->emails[] = array(
			'sender' => $sender,
			'subject' => $subject,
			'body' => $body
		);
	}
	
	protected function addToken($token,$options) {
		$this->tokens[$token] = $options;
	}
	
	public function getRandomEmail() {
		$email = $this->emails[rand(0,count($this->emails)-1)];
		
		$tokens = array_keys($this->tokens);
		$values = $this->_randomTokenValues();
		
		// Subject
		$email['subject'] = str_replace(
			$tokens,
			$values,
			$email['subject']
		);

		// Body
		$email['body'] = str_replace(
			$tokens,
			$values,
			$email['body']
		);
		
		return $email;
	}
	
	private function _randomTokenValues() {
		$values = array();
		
		if(is_array($this->tokens))
		foreach($this->tokens as $opts) {
			shuffle($opts);
			$values[] = array_shift($opts);
		}
		
		return $values;
	}
	
};

class CerberusSimulatorGenerator {
	private $surnames = array();
	private $names = array();
	
	function __construct() {
		/*
		 * [TODO] Move these into an American dataset for names? Allows for other name datasets
		 * to plug in for international customers.
		 */
		
		// Last Names
		$this->surnames[] = "Smith";
		$this->surnames[] = "Johnson";
		$this->surnames[] = "Williams";
		$this->surnames[] = "Jones";
		$this->surnames[] = "Brown";
		$this->surnames[] = "Davis";
		$this->surnames[] = "Miller";
		$this->surnames[] = "Wilson";
		$this->surnames[] = "Moore";
		$this->surnames[] = "Taylor";
		$this->surnames[] = "Anderson";
		$this->surnames[] = "Thomas";
		$this->surnames[] = "Jackson";
		$this->surnames[] = "White";
		$this->surnames[] = "Harris";
		$this->surnames[] = "Martin";
		$this->surnames[] = "Thompson";
		$this->surnames[] = "Garcia";
		$this->surnames[] = "Martinez";
		$this->surnames[] = "Robinson";
		$this->surnames[] = "Clark";
		$this->surnames[] = "Rodriguez";
		$this->surnames[] = "Lewis";
		$this->surnames[] = "Lee";
		$this->surnames[] = "Walker";
		$this->surnames[] = "Hall";
		$this->surnames[] = "Allen";
		$this->surnames[] = "Young";
		$this->surnames[] = "Hernandez";
		$this->surnames[] = "King";
		$this->surnames[] = "Wright";
		$this->surnames[] = "Lopez";
		$this->surnames[] = "Hill";
		$this->surnames[] = "Scott";
		$this->surnames[] = "Green";
		$this->surnames[] = "Adams";
		$this->surnames[] = "Baker";
		$this->surnames[] = "Gonzalez";
		$this->surnames[] = "Nelson";
		$this->surnames[] = "Carter";
		$this->surnames[] = "Mitchell";
		$this->surnames[] = "Perez";
		$this->surnames[] = "Roberts";
		$this->surnames[] = "Turner";
		$this->surnames[] = "Phillips";
		$this->surnames[] = "Campbell";
		$this->surnames[] = "Parker";
		$this->surnames[] = "Evans";
		$this->surnames[] = "Edwards";
		$this->surnames[] = "Collins";

		// First Names
		$this->names[] = "James";
		$this->names[] = "John";
		$this->names[] = "Robert";
		$this->names[] = "Michael";
		$this->names[] = "William";
		$this->names[] = "David";
		$this->names[] = "Richard";
		$this->names[] = "Charles";
		$this->names[] = "Joseph";
		$this->names[] = "Thomas";
		$this->names[] = "Christopher";
		$this->names[] = "Daniel";
		$this->names[] = "Paul";
		$this->names[] = "Mark";
		$this->names[] = "Donald";
		$this->names[] = "George";
		$this->names[] = "Kenneth";
		$this->names[] = "Steven";
		$this->names[] = "Edward";
		$this->names[] = "Brian";
		$this->names[] = "Ronald";
		$this->names[] = "Anthony";
		$this->names[] = "Kevin";
		$this->names[] = "Jason";
		$this->names[] = "Jeff";
		$this->names[] = "Mary";
		$this->names[] = "Patricia";
		$this->names[] = "Linda";
		$this->names[] = "Barbara";
		$this->names[] = "Elizabeth";
		$this->names[] = "Jennifer";
		$this->names[] = "Maria";
		$this->names[] = "Susan";
		$this->names[] = "Margaret";
		$this->names[] = "Dorothy";
		$this->names[] = "Lisa";
		$this->names[] = "Nancy";
		$this->names[] = "Karen";
		$this->names[] = "Betty";
		$this->names[] = "Helen";
		$this->names[] = "Sandra";
		$this->names[] = "Donna";
		$this->names[] = "Carol";
		$this->names[] = "Ruth";
		$this->names[] = "Sharon";
		$this->names[] = "Michelle";
		$this->names[] = "Laura";
		$this->names[] = "Sarah";
		$this->names[] = "Kimberly";
		$this->names[] = "Deborah";
	}
	
	private function generatePerson() {
		$firstname = $this->names[mt_rand(0,sizeof($this->names)-1)];
		$lastname = $this->surnames[mt_rand(0,sizeof($this->surnames)-1)];
		$emailaddress = sprintf("\"%s %s\" <%s.%s@cerberusdemo.com>",
			$firstname,
			$lastname,
			strtolower($firstname),
			strtolower($lastname)
		);
		
//		return array(
//			'personal' => $firstname . ' ' . $lastname,
//			'address' => $emailaddress,
//		);
		
		return $emailaddress;
	}
	
	public function generateSender($email) {

		if(empty($email['sender'])) {
			$email['sender'] = $this->generatePerson();
		}

//		$emailaddress = sprintf("\"%s %s\" <%s.%s@cerberusdemo.com>",
		return $email;
	}
};

