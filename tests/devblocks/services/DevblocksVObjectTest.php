<?php
use PHPUnit\Framework\TestCase;

class DevblocksVObjectTest extends TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testVObjectPropCaseSensitivity() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BeGiN:vCaLenDar
		METHOD:request
		prodid:Microsoft Exchange Server 2010
		vERsion:2.0
		EnD:vCALenDAR
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'Microsoft Exchange Server 2010',
			$data['VCALENDAR'][0]['props']['PRODID'][0]['value'] ?? null
		);
	}
	
	function testVObjectPropParamUnwrap() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BeGiN:vCaLenDar
		METHOD:request
		prodid:Microsoft Exchange\n
		 Server 2010
		vERsion:2.0
		EnD:vCALenDAR
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			"Microsoft Exchange\nServer 2010",
			$data['VCALENDAR'][0]['props']['PRODID'][0]['value'] ?? null
		);
	}
	
	function testVObjectPropUnescape() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BeGiN:vEVENT
		summary:This contains \"quoted text\"
		EnD:vevent
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'This contains "quoted text"',
			$data['VEVENT'][0]['props']['SUMMARY'][0]['value'] ?? null
		);
	}
	
	function testVObjectPropParamUnescape() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BeGiN:vEVENT
		ATTENDEE;CN="Bertin, Claire \"CB\"";RSVP=TRUE:mailto:c.bertin@baston.example
		EnD:vevent
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'Bertin, Claire "CB"',
			$data['VEVENT'][0]['props']['ATTENDEE'][0]['params']['CN'] ?? null
		);
	}
	
	function testVObjectPropParamQuoted() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BeGiN:vEVENT
		ATTENDEE;CN="Bertin, Claire";RSVP=TRUE:mailto:c.bertin@baston.example
		attendee;CN="Kina Halpue";RSVP=TRUE:mailto:kina@cerb.example
		EnD:vEVent
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'Kina Halpue',
			$data['VEVENT'][0]['props']['ATTENDEE'][1]['params']['CN'] ?? null
		);
	}
	
	function testVObjectPropConcat() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		begin:vcard
		name:Meister Berger
		home.label:Hufenshlagel 1234\n
		 02828 Goerlitz\n
		 Deutschland
		end:vcard
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			"Hufenshlagel 1234\n02828 Goerlitz\nDeutschland",
			$data['VCARD'][0]['props']['HOME.LABEL'][0]['value'] ?? null
		);
	}
	
	function testVObjectPropParamConcat() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		begin:vevent
		ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=Team at Ce
		 rb:mailto:team@cerb.ai
		end:vEVENT
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			"Team at Cerb",
			$data['VEVENT'][0]['props']['ATTENDEE'][0]['params']['CN'] ?? null
		);
	}
	
	function testVObjectBarePropParams() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		begin:vcard
		name:Meister Berger
		TEL;VOICE;CELL:+49 12345
		end:vcard
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			['VOICE','CELL'],
			array_keys($data['VCARD'][0]['props']['TEL'][0]['params'] ?? [])
		);
	}
	
	function testVObjectMultiplePropParam() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		begin:vexample
		PROPERTY;KEY1=VAL1;KEY2=VAL2;KEY3=VAL3:VALUE
		end:vexample
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			['KEY1','KEY2','KEY3'],
			array_keys($data['VEXAMPLE'][0]['props']['PROPERTY'][0]['params'] ?? [])
		);
	}
	
	function testVObjectPropParamQuotedSemi() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		begin:VEXAMPLE
		ATTENDEE;NAME="Bertin; Claire";RSVP=true:c.bertin@baston.example
		END:vExample
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'Bertin; Claire',
			$data['VEXAMPLE'][0]['props']['ATTENDEE'][0]['params']['NAME'] ?? null
		);
		
		$this->assertEquals(
			['NAME','RSVP'],
			array_keys($data['VEXAMPLE'][0]['props']['ATTENDEE'][0]['params'] ?? [])
		);
	}
	
	function testVObjectParse() {
		$vobject = DevblocksPlatform::services()->vobject();
		$error = null;
		
		$string = <<< 'EOD'
		BEGIN:VCALENDAR
		METHOD:REQUEST
		PRODID:Microsoft Exchange Server 2010
		VERSION:2.0
		BEGIN:VTIMEZONE
		TZID:Eastern Standard Time
		BEGIN:STANDARD
		DTSTART:16010101T020000
		TZOFFSETFROM:-0400
		TZOFFSETTO:-0500
		RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11
		END:STANDARD
		BEGIN:DAYLIGHT
		DTSTART:16010101T020000
		TZOFFSETFROM:-0500
		TZOFFSETTO:-0400
		RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3
		END:DAYLIGHT
		END:VTIMEZONE
		BEGIN:VEVENT
		ORGANIZER;CN=Claire Bertin:mailto:c.bertin@baston.example
		ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN=Team at Ce
		 rb:mailto:team@cerb.ai
		DESCRIPTION;LANGUAGE=en-US:\n______________________________________________
		 __________________________________\nMicrosoft Teams meeting\nJoin on your 
		 computer or mobile app\nClick here to join the meeting<https://teams.micro
		 soft.com/l/meetup-join/a1b2c3d4>\nOr join by entering a meeting ID\nMeetin
		 g ID: 123 456 789\n_______________________________________________________
		 _________________________\n\n
		UID:a1b2c3d4e5f60001
		SUMMARY;LANGUAGE=en-US:Chat with Cerb
		DTSTART;TZID=Eastern Standard Time:20220622T160000
		DTEND;TZID=Eastern Standard Time:20220622T163000
		CLASS:PUBLIC
		PRIORITY:5
		DTSTAMP:20220621T150314Z
		TRANSP:OPAQUE
		STATUS:CONFIRMED
		SEQUENCE:0
		LOCATION;LANGUAGE=en-US:Microsoft Teams Meeting
		X-MICROSOFT-CDO-APPT-SEQUENCE:0
		X-MICROSOFT-CDO-OWNERAPPTID:123456
		X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE
		X-MICROSOFT-CDO-INTENDEDSTATUS:BUSY
		X-MICROSOFT-CDO-ALLDAYEVENT:FALSE
		X-MICROSOFT-CDO-IMPORTANCE:1
		X-MICROSOFT-CDO-INSTTYPE:0
		X-MICROSOFT-ONLINEMEETINGEXTERNALLINK:
		X-MICROSOFT-DONOTFORWARDMEETING:FALSE
		X-MICROSOFT-DISALLOW-COUNTER:FALSE
		X-MICROSOFT-LOCATIONS:[ { "DisplayName" : "Microsoft Teams Meeting"\, "Loca
		 tionAnnotation" : ""\, "LocationSource" : 0\, "Unresolved" : false\, "Loca
		 tionUri" : "" } ]
		BEGIN:VALARM
		DESCRIPTION:REMINDER
		TRIGGER;RELATED=START:-PT15M
		ACTION:DISPLAY
		END:VALARM
		END:VEVENT
		END:VCALENDAR
		EOD;
		
		$data = $vobject->parse($string, $error);
		
		$this->assertEquals(
			'Microsoft Exchange Server 2010',
			$data['VCALENDAR'][0]['props']['PRODID'][0]['value'] ?? null
		);
		
		$this->assertEquals(
			'a1b2c3d4e5f60001',
			$data['VCALENDAR'][0]['children']['VEVENT'][0]['props']['UID'][0]['value'] ?? null 
		);
		
		$this->assertEquals(
			'Claire Bertin',
			$data['VCALENDAR'][0]['children']['VEVENT'][0]['props']['ORGANIZER'][0]['params']['CN'] ?? null 
		);
		
		$this->assertEquals(
			'-PT15M',
			$data['VCALENDAR'][0]['children']['VEVENT'][0]['children']['VALARM'][0]['props']['TRIGGER'][0]['value'] ?? null 
		);
	}
}