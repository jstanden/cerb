<?php
namespace Cerb\Impex\Exporters {
	use Cerb\Impex\CerbImpex;
	abstract class Exporter {
		protected $_config = array();
		
		function __construct(array $config) {
			$this->setConfig($config);
		}
		
		function setConfig(array $config) {
			$this->testConfig($config);
			$this->_config = $config;
			return true;
		}
		
		abstract function testConfig(array $config);
		abstract function export();
	}
	
	class Cerb7 extends Exporter {
		private $_db = null;
		
		function __construct($config) {
			parent::__construct($config);
		}
		
		private function _getDatabase() {
			if(is_null($this->_db)) {
				$this->_db = mysqli_connect($this->_config['db_host'], $this->_config['db_user'], $this->_config['db_pass'], $this->_config['db_name']);
				mysqli_query($this->_db, 'SET group_concat_max_len = 1024000');
				mysqli_query($this->_db, "SET NAMES 'utf8'");
			}
			
			return $this->_db;
		}
		
		function testConfig(array $config) {
			$required_options = array(
				'db_host',
				'db_name',
				'db_user',
				'db_pass',
			);
			
			foreach($required_options as $opt)
				if(!isset($config[$opt]))
					die(sprintf("[ERROR] The '%s' option is required.\n", $opt));
			
			if(!extension_loaded('mysqli'))
				die("[ERROR] The 'mysqli' extension is required.");
				
			// Test the MySQL connection
			$db = mysqli_connect($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
			
			if(false == $db)
				die("[ERROR] Can't connect to the given database.\n");
				
			// [TODO] SET NAMES
			// [TODO] Check the table schemas + Cerb version
			
			return true;
		}
		
		// [TODO] CSV
		private function _exportOrgs() {
			$db = $this->_getDatabase();
			$sql = "SELECT id, name, street, city, province, postal, country, phone, website, created, updated FROM contact_org";
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 1000);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($id, $name, $street, $city, $province, $postal, $country, $phone, $website, $created, $updated);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '03-orgs-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$doc = simplexml_load_string('<organization/>');
					$doc->name = $name;
					$doc->street = $street;
					$doc->city = $city;
					$doc->province = $province;
					$doc->postal = $postal;
					$doc->country = $country;
					$doc->phone = $phone;
					$doc->website = $website;
					$doc->created = $created;
					$doc->updated -> $updated;
					
					$doc->asXML(sprintf("%s%09d.xml", $dir, $id));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportTickets() {
			$db = $this->_getDatabase();
			
			$mask_prefix = $this->_config['mask_prefix'] ?? null;
			
			if(false == (@$storage_path = $this->_config['storage_path']))
				die("The 'storage_path' configuration setting is required.\n");
			
			// Sanitize the path
			$storage_path = rtrim($storage_path, '\\/') . '/';
			
			if(!file_exists($storage_path))
				die(sprintf("The 'storage_path' (%s) doesn't exist.\n", $storage_path));
			
			$sql = <<< SQL
SELECT t.id, t.mask, t.subject, t.status_id, t.created_date, t.updated_date, g.name, b.name, 
(SELECT name FROM contact_org WHERE id = t.org_id) AS org_name, 
(SELECT group_concat(address.email) FROM requester INNER JOIN address ON (address.id=requester.address_id) where requester.ticket_id=t.id) AS participants, 
(SELECT group_concat(comment.id) FROM comment WHERE context = 'cerberusweb.contexts.ticket' AND context_id = t.id) AS comment_ids
FROM ticket t 
INNER JOIN worker_group g ON (t.group_id=g.id)
INNER JOIN bucket b ON (t.bucket_id=b.id)
WHERE status_id != 3
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 1000);
			
			$count = 0;
			$bins = 0;
			
			$statuses = array(
				0 => 'open',
				1 => 'waiting',
				2 => 'closed',
				3 => 'deleted',
			);
			
			if($stmt->execute()) {
				$stmt->bind_result($id, $mask, $subject, $status_id, $created_date, $updated_date, $group_name, $bucket_name, $org_name, $participants, $comment_ids);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-tickets-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$doc = simplexml_load_string('<ticket/>');
					$doc->mask = $mask_prefix . $mask;
					$doc->subject = $subject;
					$doc->org = $org_name;
					$doc->group = $group_name;
					$doc->bucket = $bucket_name;
					$doc->status_id = $status_id;
					$doc->status = $statuses[$status_id];
					$doc->created_date = $created_date;
					$doc->updated_date = $updated_date;
					
					// Participants
					
					$xml_requesters = $doc->addChild('requesters');
					foreach(explode(',', $participants) as $participant)
						$xml_requesters->addChild('address', $participant);
					
					// Messages
					
					$xml_messages = $doc->addChild('messages');
					
					$sql_messages = <<< SQL
SELECT m.created_date, m.is_outgoing, 
(SELECT headers FROM message_headers WHERE message_id = m.id) as headers,
(SELECT data FROM storage_message_content WHERE chunk = 1 AND id = m.id) as content,
(SELECT GROUP_CONCAT(to_context_id) FROM context_link WHERE from_context = 'cerberusweb.contexts.message' AND from_context_id = m.id AND to_context = 'cerberusweb.contexts.attachment') AS attachment_ids
FROM message m 
WHERE ticket_id = $id
SQL;
					
					$stmt_msgs = $db->prepare($sql_messages);
					$stmt_msgs->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
					$stmt_msgs->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 100);
					
					if($stmt_msgs->execute()) {
						$stmt_msgs->bind_result($created_date, $is_outgoing, $headers, $content, $attachment_ids);
						
						while($stmt_msgs->fetch()) {
							$xml_message = $xml_messages->addChild('message');
							$xml_message->is_outgoing = intval($is_outgoing);
							
							// Headers
							$xml_headers = $xml_message->addChild('headers');
							$xml_headers->date = date('r', $created_date);
							$xml_message->headers = $headers;

							// Content
							$xml_content = $xml_message->addChild('content', base64_encode($content));
							$xml_content['encoding'] = 'base64';
							
							// Attachments
							if(!empty($attachment_ids)) {
								$xml_attachments = $xml_message->addChild('attachments');
								
								$sql_attachments = sprintf("SELECT id, name, mime_type, storage_size, storage_key, storage_extension FROM attachment WHERE id IN (%s)", $attachment_ids);
								$res = $db->query($sql_attachments);
								
								if($res && $res instanceof \mysqli_result)
								while($row = $res->fetch_assoc()) {
									$file_path = $storage_path . 'attachments/' . $row['storage_key'];
									
									if(file_exists($file_path) && is_readable($file_path)) {
										$xml_attachment = $xml_attachments->addChild('attachment');
										$xml_attachment->name = $row['name'];
										$xml_attachment->mimetype = $row['mime_type'];
										$xml_attachment->size = $row['storage_size'];
										
										$xml_attachment_content = $xml_attachment->addChild('content', base64_encode(file_get_contents($file_path)));
										$xml_attachment_content['encoding'] = 'base64';
									}
								}
								
								$res->close();
							}
						}
						
						$stmt_msgs->close();
					}
					
					// Comments
					
					if(!empty($comment_ids)) {
						$xml_comments = $doc->addChild('comments');
						
						$sql_comments = sprintf(
							"SELECT c.created, c.comment, a.email as owner_email FROM comment AS c INNER JOIN address a on (c.owner_context_id=a.id) WHERE c.owner_context = 'cerberusweb.contexts.address' and c.id in (%s) ".
							"UNION ALL ".
							"SELECT c.created, c.comment, a.email as owner_email FROM comment AS c INNER JOIN worker w on (w.id=c.owner_context_id) INNER JOIN address a on (w.email_id=a.id) WHERE c.owner_context = 'cerberusweb.contexts.worker' and c.id in (%s) ".
							"UNION ALL ".
							"SELECT c.created, c.comment, '' as owner_email FROM comment AS c WHERE c.owner_context not in ('cerberusweb.contexts.address','cerberusweb.contexts.worker') and c.id in (%s) "
							,
							$comment_ids,
							$comment_ids,
							$comment_ids
						);
						
						//$sql_comments = sprintf("SELECT created, comment, owner_context, owner_context_id FROM comment WHERE id IN (%s)", $comment_ids);
						$res = $db->query($sql_comments);
						
						if($res && $res instanceof \mysqli_result && $res->num_rows)
						while($row = $res->fetch_assoc()) {
							$xml_comment = $xml_comments->addChild('comment');
							$xml_comment->created_date = intval($row['created']);
							$xml_comment->author = $row['owner_email'];
							
							$xml_comment_content = $xml_comment->addChild('content', base64_encode($row['comment']));
							$xml_comment_content['encoding'] = 'base64';
						}
						
						$res->close();
					}
					
					$doc->asXML(sprintf("%s%09d.xml", $dir, $id));
				}
				
				$stmt->close();
			}
		}
		
		function export() {
			//$this->_exportOrgs();
			$this->_exportTickets();
		}
	}
}

namespace Cerb\Impex {

class CerbImpex {
	static private $_exporter = null;
	static private $_options = array();
	
	static function init() {
		// Verify CLI usage
		if('cli' != php_sapi_name())
			die("This script must be executed from the command line.");
		
		// Load CLI arguments
		$options = getopt('c:o:', array(
			'config:',
			'output:',
			'help'
		));
		
		$config_file = @$options['config'] ?: @$options['c'] ?: false;
		$output_dir = @$options['output'] ?: @$options['o'] ?: false;
		
		if(isset($options['help']) || !$config_file) {
			self::_printHelp();
			exit;
		}
		
		// Check config file
		
		if(!$config_file || !file_exists($config_file))
			die("[ERROR] The --config option is required.\n");
		
		if(false == ($config = json_decode(file_get_contents($config_file), true)))
			die("[ERROR] Can't read the configuration file.\n");
		
		// Check output dir
		
		if(!$output_dir || (!is_dir($output_dir) && !mkdir($output_dir, 0700, true)))
			die("[ERROR] The --output option is required.\n");
		
		// Sanitize
		$output_dir = rtrim($output_dir,'\\/') . '/';
		
		// Check the exporter class
		if(
			false == (@$exporter_class = 'Cerb\\ImpEx\\Exporters\\' . $config['exporter']['source']) 
			|| !class_exists($exporter_class)) 
				die("[ERROR] Invalid exporter class.\n");
			
		// Check for options
		if(false == (@$options = $config['exporter']['options']) || !is_array($options))
			die("[ERROR] Config: ['exporter']['options'] is required.\n");
		
		if(false == ($exporter = new $exporter_class($options)))
			die("[ERROR] Failed to load the exporter class.\n");
		
		self::$_exporter = $exporter;
		self::$_options = array(
			'output_dir' => $output_dir,
		);
		return true;
	}
	
	private function _printHelp() {
		echo 'Usage: php ' . basename(__FILE__) . ' -c <config.json> [options]' . PHP_EOL;
		echo <<< EOF
--help
	Show available options.
-c, --config <file>
	The configuration file to use.
-o, --output <dir>
	The output directory for writing the export.

EOF;
	}
	
	static function getOption($key, $default=null) {
		if(isset(self::$_options[$key]))
			return self::$_options[$key];
		
		return $default;
	}
	
	static function getOptions() {
		return self::$_options;
	}
	
	static function export() {
		if(!self::$_exporter)
			die("[ERROR] No exporter configured.\n");
		
		self::$_exporter->export();
	}
}

date_default_timezone_set('GMT');

CerbImpex::init();
CerbImpEx::export();
}