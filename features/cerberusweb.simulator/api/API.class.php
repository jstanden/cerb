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
		// Last Names
		$this->surnames[] = "Adams";
		$this->surnames[] = "Allen";
		$this->surnames[] = "Anderson";
		$this->surnames[] = "Bailey";
		$this->surnames[] = "Baker";
		$this->surnames[] = "Bertin";
		$this->surnames[] = "Brouwer";
		$this->surnames[] = "Brown";
		$this->surnames[] = "Bruno";
		$this->surnames[] = "Campbell";
		$this->surnames[] = "Carter";
		$this->surnames[] = "Chang";
		$this->surnames[] = "Chen";
		$this->surnames[] = "Clark";
		$this->surnames[] = "Collins";
		$this->surnames[] = "Cooper";
		$this->surnames[] = "Costa";
		$this->surnames[] = "Davis";
		$this->surnames[] = "De Vos";
		$this->surnames[] = "Dubois";
		$this->surnames[] = "Dumont";
		$this->surnames[] = "Edwards";
		$this->surnames[] = "Engelbrecht";
		$this->surnames[] = "Evans";
		$this->surnames[] = "Gallo";
		$this->surnames[] = "Garcia";
		$this->surnames[] = "Gibson";
		$this->surnames[] = "Gonzalez";
		$this->surnames[] = "Gray";
		$this->surnames[] = "Green";
		$this->surnames[] = "Hall";
		$this->surnames[] = "Harris";
		$this->surnames[] = "Hernandez";
		$this->surnames[] = "Hill";
		$this->surnames[] = "Jackson";
		$this->surnames[] = "Jacobs";
		$this->surnames[] = "Janssen";
		$this->surnames[] = "Johnson";
		$this->surnames[] = "Jones";
		$this->surnames[] = "Joubert";
		$this->surnames[] = "Khan";
		$this->surnames[] = "King";
		$this->surnames[] = "Kruger";
		$this->surnames[] = "Krupin";
		$this->surnames[] = "Lacroix";
		$this->surnames[] = "Lee";
		$this->surnames[] = "Levy";
		$this->surnames[] = "Lewis";
		$this->surnames[] = "Lopez";
		$this->surnames[] = "Martin";
		$this->surnames[] = "Martinez";
		$this->surnames[] = "Miller";
		$this->surnames[] = "Mitchell";
		$this->surnames[] = "Moore";
		$this->surnames[] = "Moreno";
		$this->surnames[] = "Moretti";
		$this->surnames[] = "Mulder";
		$this->surnames[] = "Nelson";
		$this->surnames[] = "Nguyen";
		$this->surnames[] = "Parker";
		$this->surnames[] = "Perez";
		$this->surnames[] = "Phillips";
		$this->surnames[] = "Polzin";
		$this->surnames[] = "Reynaud";
		$this->surnames[] = "Roberts";
		$this->surnames[] = "Robinson";
		$this->surnames[] = "Rodriguez";
		$this->surnames[] = "Rossi";
		$this->surnames[] = "Scott";
		$this->surnames[] = "Smith";
		$this->surnames[] = "Stevens";
		$this->surnames[] = "Taylor";
		$this->surnames[] = "Theron";
		$this->surnames[] = "Thomas";
		$this->surnames[] = "Thompson";
		$this->surnames[] = "Turner";
		$this->surnames[] = "Vetrov";
		$this->surnames[] = "Volkov";
		$this->surnames[] = "Vos";
		$this->surnames[] = "Walker";
		$this->surnames[] = "White";
		$this->surnames[] = "Williams";
		$this->surnames[] = "Wilson";
		$this->surnames[] = "Wright";
		$this->surnames[] = "Yamikov";
		$this->surnames[] = "Young";
		$this->surnames[] = "Zhong";
		$this->surnames[] = "de Graaf";

		// First Names
		$this->names[] = "Adrien";
		$this->names[] = "Alessio";
		$this->names[] = "Anthony";
		$this->names[] = "Barbara";
		$this->names[] = "Bethany";
		$this->names[] = "Betty";
		$this->names[] = "Bill";
		$this->names[] = "Bradley";
		$this->names[] = "Brian";
		$this->names[] = "Callum";
		$this->names[] = "Carol";
		$this->names[] = "Charles";
		$this->names[] = "Charlotte";
		$this->names[] = "Christian";
		$this->names[] = "Christopher";
		$this->names[] = "Claire";
		$this->names[] = "Daniel";
		$this->names[] = "Darren";
		$this->names[] = "David";
		$this->names[] = "Davide";
		$this->names[] = "Deborah";
		$this->names[] = "Dmitry";
		$this->names[] = "Donald";
		$this->names[] = "Donna";
		$this->names[] = "Dorothy";
		$this->names[] = "Edward";
		$this->names[] = "Elizabeth";
		$this->names[] = "Emershan";
		$this->names[] = "Esmee";
		$this->names[] = "Ethan";
		$this->names[] = "Eva";
		$this->names[] = "François";
		$this->names[] = "Félix";
		$this->names[] = "George";
		$this->names[] = "Giada";
		$this->names[] = "Grace";
		$this->names[] = "Helen";
		$this->names[] = "Hunter";
		$this->names[] = "Inès";
		$this->names[] = "Isabella";
		$this->names[] = "Jack";
		$this->names[] = "James";
		$this->names[] = "Jana";
		$this->names[] = "Jason";
		$this->names[] = "Jeandré";
		$this->names[] = "Jeff";
		$this->names[] = "Jennifer";
		$this->names[] = "Jill";
		$this->names[] = "John";
		$this->names[] = "Joseph";
		$this->names[] = "Karen";
		$this->names[] = "Karina";
		$this->names[] = "Kayla";
		$this->names[] = "Ken";
		$this->names[] = "Kenneth";
		$this->names[] = "Kevin";
		$this->names[] = "Kimberly";
		$this->names[] = "Kyan";
		$this->names[] = "Laura";
		$this->names[] = "Linda";
		$this->names[] = "Lisa";
		$this->names[] = "Luuk";
		$this->names[] = "Margaret";
		$this->names[] = "Maria";
		$this->names[] = "Marie";
		$this->names[] = "Mark";
		$this->names[] = "Mary";
		$this->names[] = "Mathias";
		$this->names[] = "Michael";
		$this->names[] = "Michelle";
		$this->names[] = "Mila";
		$this->names[] = "Nancy";
		$this->names[] = "Patricia";
		$this->names[] = "Paul";
		$this->names[] = "Richard";
		$this->names[] = "Robert";
		$this->names[] = "Ronald";
		$this->names[] = "Ruth";
		$this->names[] = "Ryder";
		$this->names[] = "Sandra";
		$this->names[] = "Sarah";
		$this->names[] = "Scott";
		$this->names[] = "Sergey";
		$this->names[] = "Sharon";
		$this->names[] = "Sofia";
		$this->names[] = "Steven";
		$this->names[] = "Susan";
		$this->names[] = "Sveta";
		$this->names[] = "Tatiana";
		$this->names[] = "Teun";
		$this->names[] = "Thomas";
		$this->names[] = "Tom";
		$this->names[] = "William";
		$this->names[] = "Yaseen";
		$this->names[] = "Zoë";
		
	}
	
	private function generatePerson() {
		$firstname = $this->names[mt_rand(0,sizeof($this->names)-1)];
		$lastname = $this->surnames[mt_rand(0,sizeof($this->surnames)-1)];
		$emailaddress = sprintf("\"%s %s\" <%s.%s@cerberusdemo.com>",
			$firstname,
			$lastname,
			DevblocksPlatform::strLower($firstname),
			DevblocksPlatform::strLower($lastname)
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

