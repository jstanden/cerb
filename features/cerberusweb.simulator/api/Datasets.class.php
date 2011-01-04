<?php
/***********************************************************************
| Cerberus Helpdesk(tm) developed by WebGroup Media, LLC.
|-----------------------------------------------------------------------
| All source code & content (c) Copyright 2011, WebGroup Media LLC
|   unless specifically noted otherwise.
|
| This source code is released under the Cerberus Public License.
| The latest version of this license can be found here:
| http://www.cerberusweb.com/license.php
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
 * - Jeff Standen, Darren Sugita, Dan Hildebrandt, Scott Luther,
 * 		and Jerry Kanoholani. 
 *	 WEBGROUP MEDIA LLC. - Developers of Cerberus Helpdesk
 */
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


class GovDataset extends SimulatorDataset {
	
	public function __construct() {
		$this->addEmailTemplate('Do you ##report_to## the military?','Does your ##agency## ##report_to## the military?');
		$this->addEmailTemplate('Who do you ##report_to##?','What ##agency## do you ##report_to##?');
		$this->addEmailTemplate('Services available?','What ##type## of services do you ##offer##?');
		$this->addEmailTemplate('##grant##','How do I ##obtain## ##grant## from your agency?');
		$this->addEmailTemplate('Are you hiring?','How can I ##work_for## your ##agency##?');
		$this->addEmailTemplate('Need help','Who can I ##contact## to get ##info##?');
		$this->addEmailTemplate('##operating## hours?','What are the normal ##operating## hours of your ##agency##?');
		$this->addEmailTemplate('Staffing','How many ##people## do you have working for your ##agency##?');
		$this->addEmailTemplate('How long?','How many ##time_unit## does it take to process a ##request##?');
		$this->addEmailTemplate('Need info','Where is the ##contact_info## for your ##agency##?');
				
		$this->addToken('##agency##',array('field office','location','office','branch','division','organization','agency','department','jurisdiction'));
		$this->addToken('##report_to##',array('collaborate with','deal with','partner with','fall under','work with','report to'));
		$this->addToken('##type##',array('type','kind','kinds','types'));
		$this->addToken('##offer##',array('offer','have','give','provide'));
		$this->addToken('##obtain##',array('obtain','get','locate','receive'));
		$this->addToken('##grant##',array('grants','subsidies','help','money','funds'));
		$this->addToken('##work_for##',array('join','enter','seek employment in'));
		$this->addToken('##contact##',array('call','email','contact','converse with'));
		$this->addToken('##info##',array('help','support','questions answered','answers','information'));
		$this->addToken('##operating##',array('operating','working','opening','closing'));
		$this->addToken('##people##',array('people','employees','volunteers','workers'));
		$this->addToken('##time_unit##',array('hours','days','weeks','months'));
		$this->addToken('##request##',array('query','request','submission','question'));
		$this->addToken('##contact_info##',array('contact information','fax number','details','directions','address'));
	}
	
};


class HostingDataset extends SimulatorDataset {
	
	public function __construct() {
	    $domain_extensions = array('com'=>'com','net'=>'net','org'=>'org');
	    
		$this->addEmailTemplate('##site## down','I can\'t ##access## my ##site##?');
		$this->addEmailTemplate('How do I ##login## with ##product##?','I just finished ##signup_present## for ##product##.  How do I ##login## to my account?');
		$this->addEmailTemplate('##lost## ##password##','I ##lost## my ##password##, how do I get it back?');
		$this->addEmailTemplate('##docs##?','I\'m ##emotion##.  Where is your ##docs##?');
		$this->addEmailTemplate('How long for new ##product##?','I just ##signup_past## ##product##.  How long does it take to get my new account ##online##?');
		$this->addEmailTemplate('how much is ##product##?','Where can I find the ##cost## for ##product##?');
		$this->addEmailTemplate('##refund##?','I don\'t like your ##feature##.  How can I get ##refund##?');
		$this->addEmailTemplate('different options for ##product##?','Do you ##offer## different options for ##product##?  I couldn\'t find the info on your website.');
		$this->addEmailTemplate('Need help','How do I ##web_action## ##filetype## files?');
		$this->addEmailTemplate('How can I pay?','What ##types## of ##payment## do you accept for ##product##?');
		$this->addEmailTemplate('Hosting Industry Newsletter (The Future of ##using_tool##?)',"===============\r\nHOSTING TOOLS\r\n===============\r\nDid you know ##using_percent## website owners are using ##using_tool## compared to last year?\r\n\r\n===============\r\nPRICING\r\n===============\r\nThis week the average website owner surveyed said they would pay ##hosting_mopay##/mo for ##product##.",'"Hosting Industry News" <hosting-news@cerberusdemo.com>');
		$this->addEmailTemplate('New Order: ##hosting_domain## (##hosting_plan## package)',"A new user signed up for the ##hosting_plan## hosting package!\r\n\r\nDomain: ##hosting_domain##",'"New Orders" <hosting-orders@cerberusdemo.com>');
		
		$this->addToken('##site##',array('site','website','webpage','control panel'));
		$this->addToken('##access##',array('access','see','find','download','surf','visit'));
		$this->addToken('##login##',array('setup','upload','login'));
		$this->addToken('##signup_present##',array('purchasing','signing up','ordering','buying','paying','setting up'));
		$this->addToken('##lost##',array('forgot','misplaced','lost'));
		$this->addToken('##password##',array('password','login','details'));
		$this->addToken('##emotion##',array('lost','frustrated','annoyed','searching','confused','feeling like a newbie','just starting out'));
		$this->addToken('##docs##',array('documentation','support','help','support material','manual'));
		$this->addToken('##signup_past##',array('purchased','signed up','started'));
		$this->addToken('##online##',array('online','up','working','started','activated'));
		$this->addToken('##cost##',array('pricing','cost','bundles'));
		$this->addToken('##product##',array('dedicated hosting','reseller hosting','PHP hosting','Ruby hosting','developer hosting','professional hosting','business hosting','economy hosting','application hosting','Unix hosting','Windows hosting'));
		$this->addToken('##feature##',array('control panel','lack of secure ftp','bandwidth limit','corporate mascot','storage limitations','PHP version','server speed'));
		$this->addToken('##refund##',array('a refund','my money back','refunded','someone to call me'));
		$this->addToken('##offer##',array('offer','give','provide','allow'));
		$this->addToken('##web_action##',array('stream','upload','embed'));
		$this->addToken('##filetype##',array('mp3','mpeg','quicktime','flash','video','podcast'));
		$this->addToken('##types##',array('types','forms','kinds'));
		$this->addToken('##payment##',array('payment','purchase','billing','payment types'));
		$this->addToken('##using_percent##',array('50% more','25% less','30% more'));
		$this->addToken('##using_tool##',array('PHP','Java','Ruby on Rails','Perl','Coldfusion','ASP.NET'));
		$this->addToken('##hosting_plan##',array('Platinum','Gold','Silver','Copper'));
		$this->addToken('##hosting_domain##',array(
		    'business.'.array_rand($domain_extensions),
		    'shopping.'.array_rand($domain_extensions),
		    'fitness.'.array_rand($domain_extensions),
		    'finance.'.array_rand($domain_extensions),
		    'coffee.'.array_rand($domain_extensions)
		    ));
		$this->addToken('##hosting_mopay##',array('$25-$30','$5-$10','$1.25-$2.50'));
	}
	
};


class NPODataset extends SimulatorDataset {
	
	public function __construct() {
		$this->addEmailTemplate('What do you do?','What kind of ##work## does your ##organization## do?');
		$this->addEmailTemplate('Recent ##news##?','Is there any recent ##news## about the organization\'s work in the ##location##?');
		$this->addEmailTemplate('I want to help','How can I ##finance## your ##organization##?');
		$this->addEmailTemplate('I want to help','Can I ##help## with my time instead of a ##donation##?');
		$this->addEmailTemplate('Tax-deductible?','If I ##finance## your organization, is my ##donation## tax deductible?');
		$this->addEmailTemplate('need ##receipt##','How do I get ##receipt## for my ##donation##?');
		$this->addEmailTemplate('What do you do?','Does your ##organization## work with any local ##charities##?');
		$this->addEmailTemplate('need help','Do I qualify to ##obtain## help from your ##organization##?');
		$this->addEmailTemplate('Who do you help?','Will local ##group## benefit from my ##donation##?');
		$this->addEmailTemplate('Leave me alone!','Will your ##organization## stop ##harassing## me?');
		
		$this->addToken('##work##',array('work','help','community involvement','fundraising'));
		$this->addToken('##organization##',array('organization','company','N.P.O.','non-profit'));
		$this->addToken('##news##',array('word','news','reports','articles'));
		$this->addToken('##location##',array('area','community','neighborhood','city','county'));
		$this->addToken('##finance##',array('subscribe to','donate to','finance','give funds to','support','give money to'));
		$this->addToken('##time##',array('help','volunteer','donate','give','participate'));
		$this->addToken('##donation##',array('subscription','donation','cash donation','financial donation'));
		$this->addToken('##receipt##',array('a receipt','an invoice','a tax invoice','a tax receipt'));
		$this->addToken('##charities##',array('charities','organizations','community builders','churches','non-profits','N.P.O.s'));
		$this->addToken('##obtain##',array('receive','obtain','get','request'));
		$this->addToken('##group##',array('schools','churches','organizations','neighborhoods','people','unions'));
		$this->addToken('##harassing##',array('soliciting','approaching','talking to','harrassing','bothering'));
	}
	
};


class RetailDataset extends SimulatorDataset {
	
	public function __construct() {
		$this->addEmailTemplate('Where is my ##generic_product##?','I ##user_action_past## ##timeframe## ago, where is my ##generic_product##?');
		$this->addEmailTemplate('Where is my ##generic_product##?','When will my ##generic_product## be ##shipping_action_past##?');
		$this->addEmailTemplate('Refund, please','I am ##negative_emotion## with my ##generic_product##.  Can you please refund me for my ##generic_product##?');
		$this->addEmailTemplate('Shipping?','Do you ##shipping_action_present## to where I ##user_state_of_being##?');
		$this->addEmailTemplate('##modify## ##generic_product##','I ##desire## to ##modify## my ##generic_product##.');
		$this->addEmailTemplate('##bulk## ##discount##?','Is there a ##discount## for ##bulk## ##generic_product##s?');
		$this->addEmailTemplate('Payment methods','Do you ##accept## ##payment_method## for ##generic_product##s?');
		$this->addEmailTemplate('Payment methods?','Do you ##accept## ##payment_method## for ##payment##?');
		$this->addEmailTemplate('##previous## ##types##?','Can I ##user_action_present## ##previous## ##types## of your ##generic_product##?');
		$this->addEmailTemplate('Help, please','I tried to ##user_action_present## but nothing happened in my ##viewing_device##.');
		$this->addEmailTemplate('##expedite## ##generic_product##','Can you ##expedite## ##generic_product##s?');
		
		$this->addToken('##generic_product##',array('Code Search','Reader','Transit','Picassa','Mars','Web Accelerator'));
		$this->addToken('##user_action_past##',array('paid','bought','purchased','bought your product'));
		$this->addToken('##user_action_present##',array('pay for','buy','purchase','submit'));
		$this->addToken('##timeframe##',array('a week','three days','a month','five hours'));
		$this->addToken('##generic_product##',array('product','order','package','item','bundle'));
		$this->addToken('##shipping_action_past##',array('shipped','sent out','fulfilled','posted'));
		$this->addToken('##shipping_action_present##',array('ship','sell','deliver','post'));
		$this->addToken('##negative_emotion##',array('unhappy','dissatisfied','not satified','disappointed'));
		$this->addToken('##user_state_of_being##',array('am','was','will be','play','need'));
		$this->addToken('##desire##',array('wish','want','desire','need'));
		$this->addToken('##modify##',array('modify','change','add to','cancel'));
		$this->addToken('##discount##',array('discount','reduced price','coupon','rebate'));
		$this->addToken('##bulk##',array('bulk','large','big','a lot of'));
		$this->addToken('##accept##',array('accept','take','honor','permit'));
		$this->addToken('##payment_method##',array('Visa','MasterCard','American Express','personal checks','AMEX','Diner\'s Club','purchase orders'));
		$this->addToken('##payment##',array('payment','billing','balances','orders'));
		$this->addToken('##previous##',array('previous','old','phased out','expired','past'));
		$this->addToken('##types##',array('types','versions','kinds','configurations'));
		$this->addToken('##viewing_device##',array('browser','window','screen','account'));
		$this->addToken('##expedite##',array('expedite','priority ship','overnight ship','speed up'));
	}
	
};


class SpamDataset extends SimulatorDataset {
	
	public function __construct() {
		$this->addEmailTemplate('##account## information needs ##updated##.','As part of routine maintenance, your ##account## information needs ##updated##.  Please log into http://spam-r-us.com.');
		$this->addEmailTemplate('##stock## set to take off.','Don\'t tell anyone else, but I just heard that ##stock## is gonna announce earnings, and they\'re gonna be great!');
		$this->addEmailTemplate('get ##pill## cheap!.','You know you\'ve been looking for a way to get ##pill## without anyone knowing...  log into http://pillpalace.de now!  We ship everything in discreetly wrapped packages.');
		$this->addEmailTemplate('##lose_weight##','##lose_weight##  Get in on the latest diet trend!  Log on now to http://weightlossforeveryone.ru!');
		$this->addEmailTemplate('Confidential Business Proposal','Dear Sir, Having consulted with my colleagues and based on the information gathered from the Nigerian Chambers Of Commerce And Industry, I have the privilege to request for your assistance to transfer the sum of $47,500,000.00 (forty seven million, five hundred thousand United States dollars) into your accounts.  Please respond withyour banker\'s name, telephone, account, and fax numbers.');
		$this->addEmailTemplate('You\'ve won the ##lottery##!','Great news!  You won the ##lottery##.');
		$this->addEmailTemplate('##cheap## ##product##!','At Prestige Replica, we specialize in the sales of brand name quality replica ##product##, at some of the lowest prices possible.  Take a moment to select your choice... With our large selection of ##product## you can be sure to find that perfect one that will suit you best');
		$this->addEmailTemplate('Paying too much for your ##debt##?  Lower your payments now!','Are you paying too much for your ##debt##?  Would you like to know how to have more money to spend on toys like your neighbors?  Call loan-shark lending today!');
		$this->addEmailTemplate('##workfromhome##, Full-time and part-time','Are you sick of going to work?  Well, now you can ##workfromhome## and make as much as $6,000 per week!  Log in to http://scams-r-us.edu to find out how!');
		$this->addEmailTemplate('##random##','##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random## ##random##');
		
		$this->addToken('##account##',array('Account','Login','Payment','Credit card','Bank account'));
		$this->addToken('##updated##',array('updated','changed','confirmed','renewed'));
		$this->addToken('##stock##',array('KDJH','APDK','WODF','PRR','WLI','QFN'));
		$this->addToken('##pill##',array('V1AGRA','C1AL1S','the little blue pill','viagra','ephedra','drugs','meds','levitra','LEV1TRA'));
		$this->addToken('##lose_weight##',array('Lose weight now!','Drop those Christmas pounds...','Want your old body back?','Clothes too tight?','Women will LOVE your new figure','Watch the pounds dissapear'));
		$this->addToken('##lottery##',array('lottery','drawing','prize','trip to hawaii'));
		$this->addToken('##cheap##',array('cheap','low-price','inexpensive','half off'));
		$this->addToken('##product##',array('rolexes','cartiers','bulovas','casios'));
		$this->addToken('##debt##',array('mortgage','car loan','credit cards'));
		$this->addToken('##workfromhome##',array('work from home','have a home office','spend the day in your pajamas'));
		$this->addToken('##random##',array('allowing the case, however, to stand according to your representation','any friend of mr. bingley\'s will always be welcome here','ah! jane, i take your place now, and you must go lower, because i am a married woman.','by that time most likely captain carter would be at meryton again','You were carried away by your heat in defence of this... sea-robber.  Miss Bishop\'s scorn was almost fierce.','Ah, pardieu! Am I to understand that you are threatening me?','The llama (Lama glama) is a quadruped. It is a large camelid that originated in North America and then later on moved on to South America','Rebellion broke out when on October 29, the military command, without consultation with the government, ordered the German High Seas Fleet to sortie.','The eastern and northern slopes are protected from afternoon heat, and hence are more densely forested in oak woodlands','I write for no other purpose than to add to the beauty that now belongs to me.','Born into an acting family, Dotrice is the daughter of Roy and Kay Dotrice','General Napoleon Bonaparte overthrew the Directory government, replacing it with the Consulate. This occurred on 9 November 1799'));
	}
	
};


		