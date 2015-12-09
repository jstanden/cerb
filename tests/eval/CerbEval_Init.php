<?php
class CerbEval_Init extends PHPUnit_Framework_TestCase {
	final function __construct($name = null, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
	}
	
	function testDatabase() {
		$db = DevblocksPlatform::getDatabaseService();
		$this->assertNotNull($db);
	}
	
	function testDatabaseSchemaIsEmpty() {
		$db = DevblocksPlatform::getDatabaseService();
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
			
			DevblocksPlatform::getCacheService()->clean();
			DevblocksPlatform::getClassLoaderService()->destroy();
			
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
			DAO_Worker::TITLE => 'Support',
			DAO_Worker::AUTH_EXTENSION_ID => 'login.password',
			DAO_Worker::UPDATED => time(),
			DAO_Worker::GENDER => 'F',
		));
		
		$this->assertEquals(1, $worker_id);
		
		DAO_Worker::setAuth($worker_id, 'cerb');
		
	}
};
