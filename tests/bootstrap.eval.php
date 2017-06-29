<?php
// The URL to Selenium's WebDriver API
define('WEBDRIVER_URL', 'http://localhost:4444/wd/hub');

// The URL where you installed Cerb
define('BROWSER_URL', 'http://localhost:8080/index.php');

require(getcwd() . '/eval/CerbTestBase.php');

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

require_once('vendor/autoload.php');

class CerbTestHelper {
	static private $_instance = null;
	protected $driver = null;
	
	static function getInstance() {
		if(is_null(self::$_instance)) {
			// Pick one:
			$capabilities = DesiredCapabilities::safari();
			//$capabilities = DesiredCapabilities::firefox();
			//$capabilities = DesiredCapabilities::chrome();
			
			$driver = RemoteWebDriver::create(WEBDRIVER_URL, $capabilities, 5000, 30000);
			//$driver->manage()->window()->maximize();
			
			self::$_instance = new CerbTestHelper();
			self::$_instance->driver($driver);
		}
		
		return self::$_instance;
	}
	
	/**
	 * @param Facebook\WebDriver\Remote\RemoteWebDriver|null $driver
	 * @return Facebook\WebDriver\Remote\RemoteWebDriver|bool
	 */
	function driver($driver=null) {
		if(!is_null($driver)) {
			$this->driver = $driver;
		} else {
			return $this->driver;
		}
	}
	
	function getPathAndWait($path, $wait_secs=5) {
		$url = BROWSER_URL . $path;
		$this->driver->get($url);
		
		$this->driver->wait($wait_secs, 250)->until(
			WebDriverExpectedCondition::urlIs($url)
		);
	}
	
	function waitForPath($path, $wait_secs = 5) {
		$this->driver->wait($wait_secs, 250)->until(
			WebDriverExpectedCondition::urlIs(BROWSER_URL . $path)
		);
	}
	
	function waitForPaths(array $paths, $wait_secs = 5) {
		$this->driver->wait($wait_secs, 250)->until(
			function() use ($paths) {
				$url = $this->driver->getCurrentURL();
				
				foreach($paths as $path) {
					if(strpos($url, $path) !== false)
						return true;
				}
			}
		);
	}
	
	function getElementByAndWait($by, $wait_secs=10) {
		$driver = $this->driver;
		
		$this->driver->wait(10)->until(
			function() use (&$driver, $by) {
				try {
					$hits = $driver->findElements($by);
					return count($hits) > 0;
					
				} catch (NoSuchElementException $nse) {
					return null;
				}
			},
			sprintf('Failed to find specified elements')
		);
		
		return $this->driver->findElement($by);
	}
	
	function logInAs($email, $password) {
		$driver = $this->driver();
		
		$this->getPathAndWait('/login');
		
		$input = $driver->findElement(WebDriverBy::name('email'));
		$input->sendKeys($email);
		
		$form = $driver->findElement(WebDriverBy::id('loginForm'));
		$form->submit();
		
		$driver->wait(10)->until(
			WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
				WebDriverBy::name('password')
			)
		);
		
		$password_input = $driver->findElement(WebDriverBy::name('password'));
		$password_input->sendKeys($password);
		
		$form = $driver->findElement(WebDriverBy::id('loginForm'));
		$form->submit();
		
		$this->waitForPaths(['/welcome', '/profiles/worker/me']);
		
		sleep(1);
		
		return true;
	}
	
	function logOut() {
		$driver = $this->driver();
		
		$this->getPathAndWait('/');
		
		$worker_menu = $driver->findElement(WebDriverBy::id('lnkSignedIn'));
		$worker_menu->getLocationOnScreenOnceScrolledIntoView();
		$worker_menu->click();
		
		$by = WebDriverBy::cssSelector('#menuSignedIn li:nth-child(6) a');
		
		$driver->wait(5, 250)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		$link = $driver->findElement($by);
		$link->getLocationOnScreenOnceScrolledIntoView();
		$link->click();
		
		$this->waitForPaths(['/login']);
		
		return true;
	}
	
	function replyOnTicket($id, $reply_text) {
		$driver = $this->driver();
		
		$by = WebDriverBy::cssSelector('#conversation button.reply');
		
		$driver->wait()->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		$reply_button = $driver->findElement($by);
		$reply_button->getLocationOnScreenOnceScrolledIntoView();
		$reply_button->click();
		
		$by = WebDriverBy::cssSelector('textarea.reply[name=content]');
			
		$driver->wait(5)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		$reply_form = $driver->findElement(WebDriverBy::cssSelector('div.reply_frame form[id$=_part2]'));
		
		$textarea = $driver->findElement($by);
		$textarea->getLocationOnScreenOnceScrolledIntoView();
		$textarea->clear();
		$textarea->sendKeys($reply_text);
		
		$send_button = $reply_form->findElement(WebDriverBy::cssSelector('button.send'));
		$send_button->getLocationOnScreenOnceScrolledIntoView();
		$send_button->click();
		
		return true;
	}
	
	function commentOnTicket($id, $comment_text) {
		$driver = $this->driver();
		
		$by = WebDriverBy::id('btnComment');
			
		$driver->wait(5)->until(
			WebDriverExpectedCondition::presenceOfElementLocated($by)
		);
		
		$button = $driver->findElement($by)
			->click();
		
		$popups = [];
		
		$driver->wait(5, 250)->until(
			function() use (&$driver, &$popups) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					return 1 == count($popups);
					
				} catch(NoSuchElementException $nse) {
					return null;
				}
			},
			sprintf('Failed to open the comment popup on ticket #%d', $id)
		);
		
		$popup = reset($popups); /* @var WebDriverElement $popup */
		
		$driver->executeScript("$('textarea').trigger('autosize.destroy');");
		
		$textarea = $popup->findElement(WebDriverBy::tagName('textarea'));
		$textarea->getLocationOnScreenOnceScrolledIntoView();
		$textarea->sendKeys($comment_text);
		
		$popup->findElement(WebDriverBy::cssSelector('button.submit'))
			->click();
		
		$driver->wait(5, 250)->until(
			function() use (&$driver) {
				try {
					$popups = $driver->findElements(WebDriverBy::cssSelector('body > div.ui-dialog'));
					return empty($popups);
				} catch (NoSuchElementException $nse) {
					return true;
				}
			},
			sprintf('Failed to close the comment popup on ticket #%d', $id)
		);
		
		return true;
	}
	
	function __destruct() {
		if($this->driver)
			$this->driver->close();
	}
}
