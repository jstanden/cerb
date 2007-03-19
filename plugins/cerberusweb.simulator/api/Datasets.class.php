<?php

// [TODO] Make an extension type
class EduDataset extends SimulatorDataset {
	
	public function __construct() {
		$this->addEmailTemplate('monitor broken','My monitor is not ##functioning##, how do I ##repair## this?');
		$this->addEmailTemplate('computer hung','My computer screen ##hung##.  What do I do to make it ##unhung##?');
		$this->addEmailTemplate('Can\'t send email','I keep trying to e-mail this ##file## to my colleague, but it ##wont_work##.  What should I do?');
		$this->addEmailTemplate('Projector needed','Our ##person## needs a projector ##day##.  How do we make sure we get it?');
		$this->addEmailTemplate('##electronics## not working','The ##electronics## is not working in our ##location##.  What do we do?');
		$this->addEmailTemplate('##network## down?','The ##network## is not working in our ##location##.  How can we login?');
		$this->addEmailTemplate('##hardware## not ##functioning##','My ##hardware## stopped ##functioning##, what do I do?');
		$this->addEmailTemplate('printer out of ink','My printer is out of ##color## ink, how do I ##obtain## more?');
		$this->addEmailTemplate('Tutorials available?','Our new ##person## needs training on how to use a ##hardware##.  Does your department offer any tutorials?');
		$this->addEmailTemplate('Where is our ##hardware##?','When is our ##person## going to receive the new ##hardware##?');
		
		// substitutions
		$this->addToken('##functioning##',array('functioning','working','operating'));
		$this->addToken('##repair##',array('rectify','fix','repair','change'));
		$this->addToken('##hung##',array('froze','stopped','is stuck','turned blue'));
		$this->addToken('##unhung##',array('work','unfreeze','function','right'));
		$this->addToken('##file##',array('file','picture','movie','document','pdf','journal'));
		$this->addToken('##wont_work##',array('times out','doesn\'t send','doesn\'t work','gives me an error','is too large'));
		$this->addToken('##person##',array('department chair','keynote speaker','speaker','club','associate dean','class','teacher','student','dean','secretary','organization','team'));
		$this->addToken('##day##',array('Monday','Tuesday','Wednesday','Thursday','Friday'));
		$this->addToken('##electronics##',array('DVD sound','video display','vcr player','microphone','projector'));
		$this->addToken('##location##',array('classroom','auditorium','office','meeting room','assembly hall'));
		$this->addToken('##network##',array('internet connection','wireless','internet','connection','network'));
		$this->addToken('##hardware##',array('keyboard','mouse','monitor','printer','television','projector','spreadsheet program','media center'));
		$this->addToken('##color##',array('black','blue','red','yellow','green'));
		$this->addToken('##obtain##',array('request','order','get','obtain'));
	}
	
};

