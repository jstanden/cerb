<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\LocalFileDetector;

class CerbEval_UI_Setup extends CerbTestBase {
	function testLoginKina() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->logInAs('kina@cerb.example', 'cerb');
		
		$this->assertTrue(true);
	}
	
	public function testCloseTour() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		try {
			$hide_this = $driver->findElement(WebDriverBy::linkText('hide this'));
			
			$hide_this->click();
			
			$driver->wait(5, 250)->until(
				WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::id('tourForm'))
			);
			
		} catch (NoSuchElementException $e) {
			// This is ok
		}
		
		$this->assertTrue(true);
	}
	
	public function testAddDefaultWorkspace() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$add_page = $driver->findElement(WebDriverBy::cssSelector('BODY > ul.navmenu > li.add'));
		$add_page->click();
		
		$by = WebDriverBy::linkText('Help me create a page!');
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfAllElementsLocatedBy($by)
		);
		
		$add_link = $driver->findElement($by);
		$add_link->click();

		$by = WebDriverBy::cssSelector('#frmPageWizard button[type="button"]');
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(
				$by
			)
		);
		
		$submit = $driver->findElement($by);
		$submit->click();
		
		$by = WebDriverBy::linkText('Mail');
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(
				$by
			)
		);
		
		$this->assertTrue(true);
	}
	
	public function testSetupMailTransport() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();

		$cerb->getPathAndWait('/config/mail_smtp');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::id('popuppeek');
		
		$popup = $cerb->getElementByAndWait($by);
		
		$popup->findElement(WebDriverBy::name('name'))
			->sendKeys('Dummy Mailer');
		
		$popup->findElement(WebDriverBy::name('extension_id'))
			->sendKeys('n');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('div.mail-transport-params'), 'Null Mailer')
		);
		
		$popup->findElement(WebDriverBy::name('is_default'))
			->click();
		
		$popup->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
				} catch (NoSuchElementException $nse) {
					return true;
				}
			},
			'Failed to close the mail transport popup when creating a transport.'
		);
	
		$this->assertTrue(true);
	}
	
	public function testSetupReplyTo() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();

		$cerb->getPathAndWait('/config/mail_from');
		
		$by = WebDriverBy::cssSelector('#frmSetupMailFrom button');
		
		$driver->wait(5)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		$driver->findElement($by)
			->click();
		
		$by = WebDriverBy::id('frmAddyOutgoingPeek');
		$form = $cerb->getElementByAndWait($by);
		
		$form->findElement(WebDriverBy::name('reply_from'))
			->sendKeys('support@cerb.example');
		
		$form->findElement(WebDriverBy::name('reply_personal'))
			->sendKeys('Example Support Team');
		
		$by = WebDriverBy::name('reply_signature');
		$textarea = $cerb->getElementByAndWait($by);
		$driver->executeScript("$('textarea').trigger('autosize.destroy');");
		
		$textarea->getLocationOnScreenOnceScrolledIntoView();
		$textarea->sendKeys("-- \n{{full_name}}, {{title}}\nCerb Demo, Inc.\n");
		
		$form->findElement(WebDriverBy::name('is_default'))
			->click();
		
		$form->submit();

		$driver->wait(5)->until(
			function() use (&$driver) {
				try {
					$objects = $driver->findElements(WebDriverBy::cssSelector('#frmSetupMailFrom > fieldset'));
					return 1 == count($objects);
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Error waiting for the sender address to be created."
		);
		
		$this->assertTrue(true);
	}
	
	public function testSetupRoles() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();

		$cerb->getPathAndWait('/search/roles');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::cssSelector('div.ui-dialog');
		
		$popup = $cerb->getElementByAndWait($by);
		
		$popup->findElement(WebDriverBy::name('name'))
			->sendKeys('Everyone');
		
		$popup->findElement(WebDriverBy::cssSelector('input[name="who"][value="all"]'))
			->click();
		
		$popup->findElement(WebDriverBy::cssSelector('input[name="what"][value="all"]'))
			->click();
		
		$popup->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
				} catch (NoSuchElementException $nse) {
					return true;
				}
			},
			'Failed to close the role popup when creating a role.'
		);
		
		$this->assertTrue(true);
	}
	
	public function testSetupWorkers() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$workers = [
			[ 
				'first_name' => 'Milo',
				'last_name' => 'Dade',
				'title' => 'Software Architect',
				'email' => 'milo@cerb.example',
				'gender' => 'M',
				'at_mention_name' => 'Milo',
				'password' => 'cerb',
			],
			[ 
				'first_name' => 'Janey',
				'last_name' => 'Youve',
				'title' => 'UI/UX Designer',
				'email' => 'janey@cerb.example',
				'gender' => 'F',
				'at_mention_name' => 'Janey',
				'password' => 'cerb',
			],
			[ 
				'first_name' => 'Karl',
				'last_name' => 'Kwota',
				'title' => 'Account Manager',
				'email' => 'karl@cerb.example',
				'gender' => 'M',
				'at_mention_name' => 'Karl',
				'password' => 'cerb',
			],
			[ 
				'first_name' => 'Ned',
				'last_name' => 'Flynn',
				'title' => 'System Administrator',
				'email' => 'ned@cerb.example',
				'gender' => 'M',
				'at_mention_name' => 'Ned',
				'password' => 'cerb',
			],
			[ 
				'first_name' => 'Mara',
				'last_name' => 'Kusako',
				'title' => 'QA Lead',
				'email' => 'mara@cerb.example',
				'gender' => 'F',
				'at_mention_name' => 'Mara',
				'password' => 'cerb',
			],
		];
		
		$cerb->getPathAndWait('/search/workers');
		
		foreach($workers as $worker) {
			$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
			
			$link = $cerb->getElementByAndWait($by);
			$link->click();
			
			$by = WebDriverBy::cssSelector('div.ui-dialog');
			
			$popup = $cerb->getElementByAndWait($by);
			
			$popup->findElement(WebDriverBy::name('first_name'))
				->sendKeys($worker['first_name']);
			
			$popup->findElement(WebDriverBy::name('last_name'))
				->sendKeys($worker['last_name']);
			
			$popup->findElement(WebDriverBy::name('title'))
				->sendKeys($worker['title']);
			
			$popup->findElement(WebDriverBy::cssSelector(sprintf('input[name="gender"][value="%s"]', $worker['gender'])))
				->click();
			
			$popup->findElement(WebDriverBy::name('at_mention_name'))
				->sendKeys($worker['at_mention_name']);
			
			$popup->findElement(WebDriverBy::name('password_new'))
				->sendKeys($worker['password']);
			
			$popup->findElement(WebDriverBy::name('password_verify'))
				->sendKeys($worker['password']);
			
			$popup->findElement(WebDriverBy::cssSelector('button.chooser-create'))
				->click();
			
			// Get the last div.ui-dialog popup
			$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
			
			// There must be at least 2 popups
			$this->assertGreaterThanOrEqual(2, count($popups));
			
			$worker_popup = end($popups); // /* @var WebDriverElement $worker_popup
			
			$worker_popup->findElement(WebDriverBy::name('email'))
				->sendKeys($worker['email']);
			
			$form = $worker_popup->findElement(WebDriverBy::tagName('form'));
			$form_id = $form->getAttribute('id');
			
			$this->assertNotNull($form_id, 'Email address popup form ID was null when creating worker.');
				
			$worker_popup->findElement(WebDriverBy::cssSelector('button.submit'))
				->click();
			
			$driver->wait(5, 250)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
						return (count($popups) == 1);
						
					} catch(NoSuchElementException $nse) {
						return null;
					}
				},
				'Failed to close the email address popup when creating a worker.'
			);
			
			$form = $popup->findElement(WebDriverBy::tagName('form'));
			$form_id = $form->getAttribute('id');
			
			$this->assertNotNull($form_id, 'Worker popup form ID was null when creating worker.');
			
			$popup->findElement(WebDriverBy::cssSelector('button.submit'))
				->click();
			
			$driver->wait(5, 250)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					} catch (NoSuchElementException $nse) {
						return true;
					}
				},
				'Failed to close the worker popup when creating a worker.'
			);
			
			usleep(500000);
		}
		
		$this->assertTrue(true);
	}
	
	public function testSetupGroups() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		// ['Janey','Karl','Kina','Mara','Milo','Ned']
		$worker_ids = [3,4,1,6,2,5];
		
		$groups = [
			[ 
				'name' => 'Support',
				'is_private' => false,
				'emoji' => '😱',
				'bgindex' => 4,
				'members' => [3,6],
				'managers' => [1],
			],
			[ 
				'name' => 'Sales',
				'is_private' => false,
				'emoji' => '💸',
				'bgindex' => 3,
				'members' => [],
				'managers' => [4],
			],
			[ 
				'name' => 'Development',
				'is_private' => false,
				'emoji' => '🤖',
				'bgindex' => 2,
				'members' => [3,6],
				'managers' => [2],
			],
			[ 
				'name' => 'Corporate',
				'is_private' => true,
				'emoji' => '🎩',
				'bgindex' => 6,
				'members' => [],
				'managers' => [1],
			],
			[ 
				'name' => 'Systems',
				'is_private' => false,
				'emoji' => '🌩',
				'bgindex' => 8,
				'members' => [],
				'managers' => [5],
			],
		];
		
		$cerb->getPathAndWait('/search/groups');
		
		foreach($groups as $group) {
			$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
			
			$link = $cerb->getElementByAndWait($by);
			$link->click();
			
			$by = WebDriverBy::cssSelector('div.ui-dialog');
			
			$popup = $cerb->getElementByAndWait($by);
			
			$popup->findElement(WebDriverBy::name('name'))
				->sendKeys($group['name']);
			
			if($group['is_private']) {
				$popup->findElement(WebDriverBy::cssSelector('input[name="is_private"][value="1"]'))
					->click();
			} else {
				$popup->findElement(WebDriverBy::cssSelector('input[name="is_private"][value="0"]'))
					->click();
			}
			
			$popup->findElement(WebDriverBy::cssSelector('button.cerb-avatar-chooser'))
				->click();
			
			// Get the last div.ui-dialog popup
			$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
			
			// There must be at least 2 popups
			$this->assertGreaterThanOrEqual(2, count($popups));
			
			$avatar_popup = end($popups); // /* @var WebDriverElement $avatar_popup
			
			$color_picker = $cerb->getElementByAndWait(WebDriverBy::cssSelector('a.miniColors-trigger'));
			$color_picker->click();
			
			$driver->executeScript(sprintf("$('div.miniColors-selector div.miniColors-colorFavorites > div.miniColors-colorFavorite:nth-child(%d)').click().parent().parent().hide();", $group['bgindex']));

			$avatar_popup->findElement(WebDriverBy::name('initials'))
				->sendKeys($group['emoji']);
			
			$avatar_popup->findElement(WebDriverBy::cssSelector('fieldset.cerb-avatar-monogram button'))
				->click();
			
			$form = $avatar_popup->findElement(WebDriverBy::tagName('form'));
			$form_id = $form->getAttribute('id');
			
			$this->assertNotNull($form_id, 'Avatar chooser popup form ID was null when creating group.');
				
			$avatar_popup->findElement(WebDriverBy::cssSelector('button.canvas-avatar-export'))
				->click();

			$driver->wait(5, 250)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
						return (count($popups) == 1);
						
					} catch(NoSuchElementException $nse) {
						return null;
					}
				},
				'Failed to close the avatar chooser popup when creating a group.'
			);
			
			$form = $popup->findElement(WebDriverBy::tagName('form'));
			$form_id = $form->getAttribute('id');
			
			$this->assertNotNull($form_id, 'Group popup form ID was null when creating group.');
			
			$selects = $popup->findElements(WebDriverBy::cssSelector('div.ui-tabs div:nth-child(3) select'));
			
			$this->assertEquals(6, count($selects), "There aren't six workers in the group edit popup.");
			
			foreach($selects as $idx => &$select) {
				$worker_id = $worker_ids[$idx];
				if(in_array($worker_id, $group['managers'])) {
					$select->sendKeys('ma');
				} else if(in_array($worker_id, $group['members'])) {
					$select->sendKeys('me');
				}
			}
			
			$popup->findElement(WebDriverBy::cssSelector('button.submit'))
				->click();
			
			$driver->wait(5, 250)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					} catch (NoSuchElementException $nse) {
						return true;
					}
				},
				'Failed to close the group popup when creating a group.'
			);
			
			usleep(500000);
		}
		
		$this->assertTrue(true);
	}
	
	public function testDefaultRouting() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/mail_routing');
		
		$by = WebDriverBy::cssSelector('div.cerb-subpage > fieldset > form');
		$form = $cerb->getElementByAndWait($by);
		
		$form->findElement(WebDriverBy::name('default_group_id'))
			->sendKeys('Su');
		
		$form->submit();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$select = $driver->findElement(WebDriverBy::name('default_group_id'));
					return 1 == $select->getAttribute('value');
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Can't verify that the default group was set to Support"
		);
		
		$this->assertTrue(true);
	}
	
	public function testImportOrgs() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/search/org');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(5)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::cssSelector('div.ui-dialog form');
		
		$form = $cerb->getElementByAndWait($by);

		// We have to work around the fact that Safaridriver can't upload files
		
		$csrf_token = $form->findElement(WebDriverBy::name('_csrf_token'))->getAttribute('value');
		
		$postfields = [
			'c' => 'internal',
			'a' => 'parseImportFile',
			'context' => 'cerberusweb.contexts.org',
			'view_id' => 'search_cerberusweb_contexts_org',
			'_csrf_token' => $csrf_token,
			'csv_file' => new CURLFile(getcwd() . '/resources/Organizations.csv', 'text/csv', 'csv_file'),
		];
		
		$cookie = $driver->manage()->getCookieNamed('Devblocks');
		
		$ch = curl_init(BROWSER_URL . '/');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			sprintf("Cookie: Devblocks=%s", $cookie['value']),
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		$driver->executeScript("genericAjaxPopup('import', 'c=internal&a=showImportMappingPopup&context=cerberusweb.contexts.org&view_id=search_cerberusweb_contexts_org', null, false, '550');");
		
		$by = WebDriverBy::cssSelector('div.ui-dialog form');
		
		$driver->wait()->until(
			WebDriverExpectedCondition::elementTextContains($by, 'Associate Fields with Import Columns')
		);
		
		$form = $driver->findElement($by);
		
		$selects = $form->findElements(WebDriverBy::tagName('select'));
		
		$selects[1]->sendKeys('cc');
		$selects[3]->sendKeys('c');
		$selects[8]->sendKeys('ccc');
		
		usleep(500000);
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))->click();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
				} catch (NoSuchElementException $nse) {
					return true;
				}
			},
			'Failed to close the import popup.'
		);
	}
	
	public function testImportContacts() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/search/contacts');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(5)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::cssSelector('div.ui-dialog form');
		
		$form = $cerb->getElementByAndWait($by);

		// We have to work around the fact that Safaridriver can't upload files
		
		$csrf_token = $form->findElement(WebDriverBy::name('_csrf_token'))->getAttribute('value');
		
		$postfields = [
			'c' => 'internal',
			'a' => 'parseImportFile',
			'context' => 'cerberusweb.contexts.contact',
			'view_id' => 'search_cerberusweb_contexts_contact',
			'_csrf_token' => $csrf_token,
			'csv_file' => new CURLFile(getcwd() . '/resources/Contacts.csv', 'text/csv', 'csv_file'),
		];
		
		$cookie = $driver->manage()->getCookieNamed('Devblocks');
		
		$ch = curl_init(BROWSER_URL . '/');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			sprintf("Cookie: Devblocks=%s", $cookie['value']),
		]);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		
		$driver->executeScript("genericAjaxPopup('import', 'c=internal&a=showImportMappingPopup&context=cerberusweb.contexts.contact&view_id=search_cerberusweb_contexts_contact', null, false, '550');");
		
		$by = WebDriverBy::cssSelector('div.ui-dialog form');
		
		$driver->wait()->until(
			WebDriverExpectedCondition::elementTextContains($by, 'Associate Fields with Import Columns')
		);
		
		$form = $driver->findElement($by);
		
		$checkboxes = $form->findElements(WebDriverBy::cssSelector('input[type=checkbox]'));
		$selects = $form->findElements(WebDriverBy::tagName('select'));
		
		$checkboxes[2]->click();
		
		$selects[2]->sendKeys('ccc');
		$selects[3]->sendKeys('c');
		$selects[4]->sendKeys('ccccccc');
		$selects[7]->sendKeys('cc');
		$selects[10]->sendKeys('cccc');
		$selects[13]->sendKeys('ccccc');
		
		usleep(500000);
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))->click();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
				} catch (NoSuchElementException $nse) {
					return true;
				}
			},
			'Failed to close the import popup.'
		);
	}
	
	public function testSchedulerSetup() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/scheduler');
		
		$by = WebDriverBy::linkText('Inbound Email Message Processor');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		// Enable the email parser job
		
		$driver->findElement($by)
			->click();

		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_parser'))
		);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_parser'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_parser > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		// Enable the maint job
		
		$driver->findElement(WebDriverBy::linkText('Maintenance'))
			->click();
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_maint'))
		);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_maint'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_maint > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		// Enable the bot scheduled behavior job
		
		$driver->findElement(WebDriverBy::linkText('Bot Scheduled Behavior'))
			->click();
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_bot_scheduled_behavior'))
		);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_bot_scheduled_behavior'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_bot_scheduled_behavior > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$icons = $driver->findElements(WebDriverBy::cssSelector('div.cerb-subpage > div > div > span.glyphicons-circle-ok'));
					return(8 == count($icons));
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			"Can't verify all the scheduled jobs were enabled."
		);
	}
	
	function testCustomFieldsSetup() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/fields');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('cfTabs'))
		);
		
		$this->assertTrue(true);
	}
	
	function testPluginSetup() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/plugins');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('pluginTabs'))
		);
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('viewplugins_installed'))
		);
		
		$this->assertTrue(true);
	}
	
	function testPluginLibraryUpdate() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/plugins/library/');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('btnPluginLibrarySync'))
		);
		
		$button = $driver->findElement(WebDriverBy::id('btnPluginLibrarySync'))
			->click();
		
		$driver->wait(20, 500)->until(
			WebDriverExpectedCondition::elementTextContains(WebDriverBy::id('divPluginLibrarySync'), 'Success!')
		);
		
		$this->assertTrue(true);
	}
	
	public function testLogoutKina() {
		$cerb = CerbTestHelper::getInstance();
		
		$cerb->logOut();
		
		$this->assertTrue(true);
	}
	
	/*
	public function testQuit() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$driver->quit();
	}
	*/
	
	/*
	public function testImportOrgs() {
		$this->_runTestCaseWithPlaceholders('import/ImportOrgs.htm', array());
	}
	
	public function testImportContacts() {
		$this->_runTestCaseWithPlaceholders('import/ImportContacts.htm', array());
	}
	
	/*
	public function testSetupCustomFields() {
		$this->_runTestCase('setup/SetupCustomFields.htm');
		$this->_runTestCase('setup/custom_fields/AddTaskOwnerCustomField.htm');
	}
	*/
	
	/*
	public function testCalendars() {
		$this->_runTestCase('calendars/SwitchToKinaCalendar.htm');
		$this->_runTestCase('calendars/AddWorkSchedule.htm');
		$this->_runTestCase('calendars/AddHolidays.htm');
	}

	public function testWorkspaces() {
		$this->_runTestCase('workspaces/AddDashboardsPage.htm');
	}
	*/
};
