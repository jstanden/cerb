<?php
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverKeys;

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
			
			$driver->wait(10)->until(
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
		
		$by = WebDriverBy::cssSelector('div.help-box p:nth-of-type(3) button');
		
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

		$cerb->getPathAndWait('/search/mail_transport');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::cssSelector('div.ui-dialog');
		
		$popup = $cerb->getElementByAndWait($by);
		
		$popup->findElement(WebDriverBy::name('name'))
			->sendKeys('Dummy Mailer');
		
		$popup->findElement(WebDriverBy::name('extension_id'))
			->sendKeys('n');
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('div.mail-transport-params'), 'Null Mailer')
		);
		
		$popup->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5,250)->until(
			WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('#viewsearch_cerberusweb_contexts_mail_transport .cerb-view-marquee'), 'New email transport created'),
			'Failed to close the mail transport popup when creating a transport.'
		);
		
		$this->assertTrue(true);
	}
	
	public function testAddOutgoingEmail() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();

		$cerb->getPathAndWait('/search/address');
		
		$by = WebDriverBy::cssSelector('table.worklist tr:nth-child(1) td:nth-child(2) a:nth-child(1)');
		
		$link = $cerb->getElementByAndWait($by);
		$link->click();
		
		$by = WebDriverBy::cssSelector('div.ui-dialog');
		
		$popup = $cerb->getElementByAndWait($by);
		
		$popup->findElement(WebDriverBy::name('email'))
			->sendKeys('support@cerb.example');
		
		$popup->findElement(WebDriverBy::cssSelector('input[type=radio][name=type][value=transport]'))
			->click();
		
		$transport_autocomplete = $popup->findElement(WebDriverBy::cssSelector("button[data-field-name=mail_transport_id] + input[type=search]"));
		$transport_autocomplete->sendKeys("du");
		
		$driver->wait(10)->until(
			function() use (&$driver, &$popup) {
				try {
					$menu_items = $popup->findElements(WebDriverBy::cssSelector('ul.ui-autocomplete > li'));
					return (count($menu_items) == 1);
					
				} catch(NoSuchElementException $nse) {
					return null;
				}
			},
			'Failed to select transport from autocomplete menu when creating an address.'
		);
		
		$transport_autocomplete->sendKeys(WebDriverKeys::ARROW_DOWN);
		$transport_autocomplete->sendKeys(WebDriverKeys::ENTER);
		
		$popup->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5,250)->until(
			WebDriverExpectedCondition::elementTextContains(WebDriverBy::cssSelector('#viewsearch_cerberusweb_contexts_address .cerb-view-marquee'), 'New email address created'),
			'Failed to close the address popup when creating a sender address.'
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
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					if(empty($popups))
						return true;
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
			
			$driver->wait(10)->until(
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
			
			$driver->wait(10)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
						if(empty($popups))
							return true;
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
			
			$color_picker = $cerb->getElementByAndWait(WebDriverBy::cssSelector('div.minicolors span.minicolors-swatch'));
			$color_picker->click();
			
			$driver->executeScript(sprintf("$('div.minicolors ul.minicolors-swatches li:nth-child(%d)').click().parent().parent().hide();", $group['bgindex']));

			// [JSJ] Headless Chrome doesn't support SMP unicode characters for sendKeys(), so we have to use jQuery to set the emoji
			//$avatar_popup->findElement(WebDriverBy::name('initials'))
			//	->sendKeys($group['emoji']);
			$driver->executeScript(sprintf("$('body > div.ui-dialog:last-of-type input[name=\'initials\']').val('%s');", $group["emoji"]));
			
			$avatar_popup->findElement(WebDriverBy::cssSelector('fieldset.cerb-avatar-monogram button'))
				->click();
			
			$form = $avatar_popup->findElement(WebDriverBy::tagName('form'));
			$form_id = $form->getAttribute('id');
			
			$this->assertNotNull($form_id, 'Avatar chooser popup form ID was null when creating group.');
				
			$avatar_popup->findElement(WebDriverBy::cssSelector('button.canvas-avatar-export'))
				->click();

			$driver->wait(10)->until(
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
			
			$sender_autocomplete = $form->findElement(WebDriverBy::cssSelector("button[data-field-name=reply_address_id] + input[type=search]"));
			$sender_autocomplete->sendKeys("su");
			
			$driver->wait(10)->until(
				function() use (&$driver, &$popup) {
					try {
						$menu_items = $popup->findElements(WebDriverBy::cssSelector('ul.ui-autocomplete > li'));
						return (count($menu_items) == 1);
						
					} catch(NoSuchElementException $nse) {
						return null;
					}
				},
				'Failed to select address from autocomplete menu when creating a group.'
			);
			
			$sender_autocomplete->sendKeys(WebDriverKeys::ARROW_DOWN);
			$sender_autocomplete->sendKeys(WebDriverKeys::ENTER);
			
			$members = $popup->findElement(WebDriverBy::cssSelector('fieldset.cerb-worker-group-memberships'));
			
			$tbodys = $members->findElements(WebDriverBy::cssSelector('table > tbody'));
			$this->assertEquals(6, count($tbodys), "There aren't six workers in the members fieldset.");
			
			foreach($tbodys as $idx => &$tbody) {
				$worker_id = $worker_ids[$idx];
				
				if(in_array($worker_id, $group['managers'])) {
					$tbody->findElement(WebDriverBy::cssSelector('input[type="radio"][value="2"]'))->click();
				} else if(in_array($worker_id, $group['members'])) {
					$tbody->findElement(WebDriverBy::cssSelector('input[type="radio"][value="1"]'))->click();
				}
			}
			
			$popup->findElement(WebDriverBy::cssSelector('button.submit'))
				->click();
			
			$driver->wait(10)->until(
				function() use (&$driver) {
					try {
						$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
						if(empty($popups))
							return true;
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
		
		$cerb->getPathAndWait('/config/mail_incoming/settings');
		
		$by = WebDriverBy::cssSelector('#tabsSetupMailIncoming form');
		$form = $cerb->getElementByAndWait($by);
		
		$group_autocomplete = $form->findElement(WebDriverBy::cssSelector('button[data-field-name=default_group_id] + input[type=search]'));
		$group_autocomplete->sendKeys('Su');
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$menu_items = $driver->findElements(WebDriverBy::cssSelector('BODY > ul.ui-autocomplete > li'));
					return (count($menu_items) == 1);
					
				} catch(NoSuchElementException $nse) {
					return null;
				}
			},
			'Failed to select default group from autocomplete menu when configuring mail routing.'
		);
		
		$group_autocomplete->sendKeys(WebDriverKeys::ARROW_DOWN);
		$group_autocomplete->sendKeys(WebDriverKeys::ENTER);
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		usleep(100000);
		
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
			'a' => 'invoke',
			'module' => 'worklists',
			'action' => 'parseImportFile',
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
		
		$driver->executeScript("genericAjaxPopup('import', 'c=internal&a=invoke&module=worklists&action=renderImportMappingPopup&context=cerberusweb.contexts.org&view_id=search_cerberusweb_contexts_org', null, false, '550');");
		
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
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					if(empty($popups))
						return true;
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
			'a' => 'invoke',
			'module' => 'worklists',
			'action' => 'parseImportFile',
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
		
		$driver->executeScript("genericAjaxPopup('import', 'c=internal&a=invoke&module=worklists&action=renderImportMappingPopup&context=cerberusweb.contexts.contact&view_id=search_cerberusweb_contexts_contact', null, false, '550');");
		
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
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					if(empty($popups))
						return true;
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
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		// Enable the email parser job
		
		$driver->findElement($by)
			->click();

		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_parser'))
		);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_parser'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_parser > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		// Enable the maint job
		
		$driver->findElement(WebDriverBy::linkText('Maintenance'))
			->click();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_maint'))
		);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_maint'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$form->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_maint > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		// Enable the bot scheduled behavior job
		
		$scheduler_bot = $driver->findElement(WebDriverBy::linkText('Bot Scheduled Behavior'))
			->click();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('frmJobcron_bot_scheduled_behavior'))
		);
		
		// Scroll into view
		$driver->executeScript("arguments[0].scrollIntoView(true);", [$scheduler_bot]);
		usleep(100000);
		
		$form = $driver->findElement(WebDriverBy::id('frmJobcron_bot_scheduled_behavior'));
		
		$form->findElement(WebDriverBy::name('enabled'))
			->click();
		
		$submit = $form->findElement(WebDriverBy::cssSelector('button.submit'));
		
		$driver->executeScript("arguments[0].scrollIntoView(true);", [$submit]);
		usleep(100000);
		
		$driver->action()->moveToElement($submit)->perform();
		
		$submit->click();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('#job_cron_bot_scheduled_behavior > span.glyphicons-circle-ok'))
		);
		
		$this->assertTrue(true);
		
		$driver->wait(10)->until(
			function() use (&$driver) {
				try {
					$icons = $driver->findElements(WebDriverBy::cssSelector('div.cerb-subpage > div > div > span.glyphicons-circle-ok'));
					return(9 == count($icons));
					
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
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('cfTabs'))
		);
		
		$this->assertTrue(true);
	}
	
	function testPluginSetup() {
		$cerb = CerbTestHelper::getInstance();
		$driver = $cerb->driver();
		
		$cerb->getPathAndWait('/config/plugins');
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('pluginTabs'))
		);
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id('viewplugins_installed'))
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
