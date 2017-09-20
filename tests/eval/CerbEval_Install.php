<?php
class CerbEval_Install extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	public function testRequirements() {
		// Version
		$actual = version_compare(PHP_VERSION, "7.0", ">=");
		$this->assertEquals(true, $actual, sprintf('Cerb requires a PHP version of 7.0+, currently %s', PHP_VERSION));

		// File Uploads
		$ini_file_uploads = ini_get("file_uploads");
		$actual = ($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0);
		$this->assertEquals(true, $actual, 'In php.ini, file_uploads is not enabled.');
		
		// Memory Limit
		$expected = 16777216;
		$ini_memory_limit = ini_get("memory_limit");
		$actual = DevblocksPlatform::parseBytesString($ini_memory_limit ?: PHP_INT_MAX);
		$this->assertGreaterThanOrEqual($expected, $actual, 'Cerb requires a memory_limit in php.ini of at least 16MB');
		
		// Required extensions
		$required_extensions = array(
			'ctype',
			'curl',
			'dom',
			'gd',
			'imap',
			'json',
			'mailparse',
			'mbstring',
			'mysqli',
			'openssl',
			'pcre',
			'session',
			'simplexml',
			'spl',
			'xml',
		);
		
		foreach($required_extensions as $extension) {
			$actual = extension_loaded($extension);
			$this->assertEquals(true, $actual, sprintf('The %s extension is required.', $extension));
		}
	}
	
	function testDatabase() {
		$db = DevblocksPlatform::services()->database();
		$this->assertNotNull($db);
	}
	
	function testDatabaseSchemaIsEmpty() {
		$db = DevblocksPlatform::services()->database();
		$this->assertNotNull($db);
		
		$tables = $db->metaTables();
		$this->assertNotFalse($tables);
		
		$expected = 0;
		$actual = count($tables);

		$this->assertEquals($expected, $actual, "Database is not empty");
	}
	
	function testDatabaseCreateSchema() {
		DevblocksPlatform::getCacheService()->clean();
		
		try {
			DevblocksPlatform::init();
			
		} catch(Exception $e) {
			$this->assertTrue(false, "Failed to initialize Devblocks");
		}
		
		try {
			DevblocksPlatform::update();
			
		} catch(Exception $e) {
			$this->assertTrue(false, "Failed to initialize the Devblocks database tables");
		}
		
		$plugins = DevblocksPlatform::readPlugins();
		
		// Tailor which plugins are enabled by default
		if(is_array($plugins))
		foreach($plugins as $plugin) { /* @var $plugin DevblocksPluginManifest */
			switch ($plugin->id) {
				case 'devblocks.core':
				case 'cerberusweb.core':
				case 'cerberusweb.crm':
				case 'cerberusweb.feedback':
				case 'cerberusweb.kb':
				case 'cerberusweb.reports':
				case 'cerberusweb.support_center':
				case 'cerberusweb.simulator':
				case 'cerberusweb.timetracking':
				case 'cerb.bots.portal.widget':
				case 'cerb.project_boards':
				case 'cerb.webhooks':
					$plugin->setEnabled(true);
					break;
				
				default:
					$plugin->setEnabled(false);
					break;
			}
		}
		
		// Platform + App
		try {
			DevblocksPlatform::clearCache();
			
			CerberusApplication::update();
			
			// Reload plugin translations
			DAO_Translation::reloadPluginStrings();
			
			DevblocksPlatform::services()->cache()->clean();
			DevblocksPlatform::services()->classloader()->destroy();
			
		} catch(Exception $e) {
			$this->assertTrue(false, "Failed to initialize the Cerb database tables");
			
		}
	}
	
	function testDatabaseAddWorkers() {
		
		// Kina
		
		$address = DAO_Address::lookupAddress('kina@cerb.example', true);
		
		$this->assertInstanceOf('Model_Address', $address);
		
		$worker_id = DAO_Worker::create(array(
			DAO_Worker::EMAIL_ID => $address->id,
			DAO_Worker::FIRST_NAME => 'Kina',
			DAO_Worker::LAST_NAME => 'Halpue',
			DAO_Worker::AT_MENTION_NAME => 'Kina',
			DAO_Worker::IS_DISABLED => 0,
			DAO_Worker::IS_SUPERUSER => 1,
			DAO_Worker::LANGUAGE => 'en_US',
			DAO_Worker::TIMEZONE => 'America/Los_Angeles',
			DAO_Worker::TITLE => 'Customer Support',
			DAO_Worker::AUTH_EXTENSION_ID => 'login.password',
			DAO_Worker::UPDATED => time(),
			DAO_Worker::GENDER => 'F',
		));
		
		$this->assertEquals(1, $worker_id);
		
		// [TODO] Create a default calendar
		
		DAO_Worker::setAuth($worker_id, 'cerb');
		
	}
	
	function testCreateVersionFile() {
		$path = APP_STORAGE_PATH . 'version.php';
		$contents = sprintf('<?php define(\'APP_BUILD_CACHED\', %s);', APP_BUILD);
		
		if(!file_put_contents($path, $contents)) {
			$this->assertTrue(false, "Failed to write the version.php file in storage.");
		}
	}
};
