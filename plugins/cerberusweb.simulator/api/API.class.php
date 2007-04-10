<?php
/**
 * @author Jeff Standen <jeff@webgroupmedia.com> [JAS]
 * @author Dan Hildebrandt <dan@webgroupmedia.com> [DDH]
 */
require_once(dirname(__FILE__) . '/Datasets.class.php');

class CerberusSimulator {
	private $surnames = array();
	private $names = array();
	private $instance = null;
	
	private function __construct() {
		srand( ((int)((double)microtime()*1000003)) );
		
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
	
	public function getInstance() {
		if(@is_null($this->instance)) {
			$this->instance = new CerberusSimulator();
		}
		
		return $this->instance;
	}
	
	private function generatePerson() {
		$firstname = $this->names[rand(0,sizeof($this->names)-1)];
		$lastname = $this->surnames[rand(0,sizeof($this->surnames)-1)];
		$emailaddress = sprintf("\"%s %s\" <%s.%s@cerberusdemo.com>",
			$firstname,
			$lastname,
			strtolower($firstname),
			strtolower($lastname)
		);
		
		return array(
			'firstname' => $firstname,
			'lastname' => $lastname,
			'address' => $emailaddress,
		);
	}
	
	public function generateEmails(SimulatorDataset $dataset,$how_many=10) {
		$emails = array();
		
		for($x=0;$x<$how_many;$x++) {
			$email = $dataset->getRandomEmail();
			$email['sender'] = $this->generatePerson();
			$emails[] = $email; 
		}
		
		return $emails;
	}
	
};

class SimulatorDataset {
	protected $emails = array();
	protected $tokens = array();
	
	protected function addEmailTemplate($subject,$body) {
		$this->emails[] = array(
			'sender' => array(),
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

?>
