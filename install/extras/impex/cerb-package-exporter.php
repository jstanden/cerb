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
	
	class Cerb9 extends Exporter {
		private $_db = null;
		
		function __construct($config) {
			parent::__construct($config);
		}
		
		private function _getDatabase() {
			if(is_null($this->_db)) {
				$this->_db = mysqli_connect($this->_config['db_host'], $this->_config['db_user'], $this->_config['db_pass'], $this->_config['db_name']);
				mysqli_query($this->_db, 'SET group_concat_max_len = 4096000');
				mysqli_query($this->_db, "SET NAMES 'utf8'");
			}
			
			return $this->_db;
		}
		
		function mapGroupBucketIds($group_id, $bucket_id) {
			$group_id = intval($group_id);
			$bucket_id = intval($bucket_id);
			
			return [
				$group_id,
				$bucket_id,
			];
			
			$map = [
				1 => [1,1], // Support
				2 => [2,2], // Sales
				3 => [3,3], // Developer
				24 => [4,4], // Billing
			];
			
			if(array_key_exists($group_id, $map))
				return $map[$group_id];
			
			return [
				'{{{default.group_id}}}',
				'{{{default.bucket_id}}}',
			];
		}
		
		function mapCustomFieldId($id) {
			$id = intval($id);
			
			return $id;
			
			switch($id) {
				case 180:
					return 14;
					
				default:
					return 0;
			}
			
			/*
			$map = [
				2 => 1, // Field
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return null;
			*/
		}
		
		function mapWorkerId($id) {
			$id = intval($id);
			
			return $id;
			
			$map = [
				1 => 2, // Kina
				2 => 3, // Milo
				10 => 1, // Jeff
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return null;
		}
		
		function mapTimeTrackingActivityId($id) {
			$id = intval($id);
			
			return $id;
			
			$map = [
				1 => 1, // Development
				2 => 2, // Troubleshooting
				3 => 3, // Consulting
				4 => 4, // Sales
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return 0;
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
				
			// [TODO] Check the table schemas + Cerb version
			
			/*
			if(false == (@$storage_path = $this->_config['storage_path']))
				die("The 'storage_path' configuration setting is required.\n");
			
			// Sanitize the path
			$storage_path = rtrim($storage_path, '\\/') . '/';
			
			if(!file_exists($storage_path))
				die(sprintf("The 'storage_path' (%s) doesn't exist.\n", $storage_path));
			*/
			
			return true;
		}
		
		private function _exportWorkers() {
			$db = $this->_getDatabase();
			
			if(false == (@$storage_path = $this->_config['storage_path']))
				die("The 'storage_path' configuration setting is required.\n");
			
			// Sanitize the path
			$storage_path = rtrim($storage_path, '\\/') . '/';
			
			if(!file_exists($storage_path))
				die(sprintf("The 'storage_path' (%s) doesn't exist.\n", $storage_path));
			
			$sql = "SELECT id, first_name, last_name, title, at_mention_name, dob, gender, is_disabled, language, location, mobile, phone, time_format, timezone, ".
				"(SELECT email FROM address WHERE id = worker.email_id) AS email, ".
				"(SELECT storage_key FROM context_avatar WHERE context = 'cerberusweb.contexts.worker' AND context_id = worker.id) AS image_storage_key ".
				"FROM worker"
			;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 1000);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($id, $first_name, $last_name, $title, $at_mention_name, $dob, $gender, $is_disabled, $language, $location, $mobile, $phone, $time_format, $timezone, $email, $image_storage_key);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '01-workers-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$worker_json = [
						'uid' => sprintf('worker_%d', $id),
						'_context' => 'worker',
						'first_name' => $first_name,
						'last_name' => $last_name,
						'email' => $email,
						'title' => $title,
						'language' => $language,
						'timezone' => $timezone,
					];
					
					if($dob)
						$worker_json['dob'] = $dob;
					
					if($gender)
						$worker_json['gender'] = $gender;
					
					if($is_disabled)
						$worker_json['is_disabled'] = 1;
					
					if($location)
						$worker_json['location'] = $location;
					
					if($mobile)
						$worker_json['mobile'] = $mobile;
					
					if($phone)
						$worker_json['phone'] = $phone;
					
					if($time_format)
						$worker_json['time_format'] = $time_format;
					
					// Profile pictures from storage?
					if($image_storage_key) {
						$file_path = $storage_path . 'context_avatar/' . $image_storage_key;
						
						if(file_exists($file_path) && is_readable($file_path)) {
							$worker_json['image'] = sprintf('data:%s;base64,', 'image/png') . base64_encode(file_get_contents($file_path));
						}
					}
					
					$json_out = [$worker_json];
					
					$package_json = [
						'package' => [
							'name' => sprintf('Worker #%d', $id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '9.1.1',
								'plugins' => [],
							],
							'configure' => [
								'prompts' => [],
								'placeholders' => [],
								'options' => [
									'disable_events' => true,
								],
							],
						],
						'records' => $json_out
					];
					
					echo sprintf("Writing %s%09d.json\n", $dir, $id);
					file_put_contents(sprintf("%s%09d.json", $dir, $id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
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
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-orgs-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [
						[
							'uid' => sprintf('org_%d', $id),
							'_context' => 'org',
							'name' => $name,
							'street' => $street,
							'city' => $city,
							'province' => $province,
							'postal' => $postal,
							'country' => $country,
							'phone' => $phone,
							'website' => $website,
							'created' => $created,
							'updated' => $updated,
						]
					];
					
					$package_json = [
						'package' => [
							'name' => sprintf('Org #%d', $id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '9.1.1',
								'plugins' => [],
							],
							'configure' => [
								'prompts' => [],
								'placeholders' => [],
								'options' => [
									'disable_events' => true,
								],
							],
						],
						'records' => $json_out
					];
					
					echo sprintf("Writing %s%09d.json\n", $dir, $id);
					file_put_contents(sprintf("%s%09d.json", $dir, $id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
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
SELECT t.id, t.mask, t.subject, t.status_id, t.importance, t.created_date, t.updated_date, t.owner_id, t.group_id, t.bucket_id, o.name AS org_name, 
(SELECT group_concat(address.email) FROM requester INNER JOIN address ON (address.id=requester.address_id) where requester.ticket_id=t.id) AS participants, 
GROUP_CONCAT(cfields.field_id) AS cfield_ids, 
(SELECT group_concat(comment.id) FROM comment WHERE context = 'cerberusweb.contexts.ticket' AND context_id = t.id AND owner_context = 'cerberusweb.contexts.worker') AS comment_ids 
FROM ticket t 
LEFT JOIN (
SELECT field_id, context_id, field_value FROM custom_field_stringvalue WHERE context = 'cerberusweb.contexts.ticket' 
UNION ALL 
SELECT field_id, context_id, field_value FROM custom_field_numbervalue WHERE context = 'cerberusweb.contexts.ticket' 
UNION ALL 
SELECT field_id, context_id, field_value FROM custom_field_clobvalue WHERE context = 'cerberusweb.contexts.ticket' 
) AS cfields ON (cfields.context_id=t.id) 
LEFT JOIN contact_org o ON (o.id=t.org_id) 
WHERE 1
AND t.status_id != 3
GROUP BY t.id 
ORDER BY t.id DESC
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 1000);
			
			$count = 0;
			$bins = 0;
			
			$statuses = [
				0 => 'open',
				1 => 'waiting',
				2 => 'closed',
				3 => 'deleted',
			];
			
			if($stmt->execute()) {
				$stmt->bind_result(
					$ticket_id,
					$mask,
					$subject,
					$status_id,
					$importance,
					$created_date,
					$updated_date,
					$owner_id,
					$group_id,
					$bucket_id,
					$org_name,
					$participants,
					$cfield_ids,
					$comment_ids
				);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2500) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '03-tickets-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					$ticket_uid = sprintf('ticket_%d', $ticket_id);
					
					list($new_group_id, $new_bucket_id) = $this->mapGroupBucketIds($group_id, $bucket_id);
					
					$ticket_json = [
						'uid' => $ticket_uid,
						'_context' => 'ticket',
						'mask' => $mask_prefix . $mask,
						'subject' => $subject ?: '(no subject)',
						'importance' => $importance,
						'status' => $statuses[$status_id],
						'created' => $created_date,
						'updated' => $updated_date,
						'participants' => $participants,
					];
					
					if($new_group_id)
						$ticket_json['group_id'] = $new_group_id;
					
					if($new_bucket_id)
						$ticket_json['bucket_id'] = $new_bucket_id;
					
					if(false != ($new_owner_id = $this->mapWorkerId($owner_id)))
						$ticket_json['owner_id'] = $new_owner_id;
					
					if($org_name)
						$ticket_json['org'] = $org_name;
					
					// Ticket custom fields
					
					if($cfield_ids) {
						$sql_cfields = <<< SQL
SELECT field_id, field_value FROM custom_field_stringvalue WHERE context = 'cerberusweb.contexts.ticket' AND context_id = $ticket_id 
UNION ALL 
SELECT field_id, field_value FROM custom_field_numbervalue WHERE context = 'cerberusweb.contexts.ticket' AND context_id = $ticket_id 
UNION ALL 
SELECT field_id, field_value FROM custom_field_clobvalue WHERE context = 'cerberusweb.contexts.ticket' AND context_id = $ticket_id 
UNION ALL 
SELECT field_id, field_value FROM custom_field_geovalue WHERE context = 'cerberusweb.contexts.ticket' AND context_id = $ticket_id
SQL;
					
						$stmt_cfields = $db->prepare($sql_cfields);
						
						if($stmt_cfields->execute()) {
							$stmt_cfields->bind_result(
								$cfield_id,
								$cfield_value
							);
							
							while($stmt_cfields->fetch()) {
								if(false == ($new_cfield_id = $this->mapCustomFieldId($cfield_id)))
									continue;
								
								// If multi-value, append to an array
								if(array_key_exists('custom_' . $new_cfield_id, $ticket_json)) {
									if(!is_array($ticket_json['custom_' . $new_cfield_id])) {
										$ticket_json['custom_' . $new_cfield_id] = [$ticket_json['custom_' . $new_cfield_id]];
									}
									
									$ticket_json['custom_' . $new_cfield_id][] = $cfield_value;
									
								} else {
									$ticket_json['custom_' . $new_cfield_id] = $cfield_value;
								}
							}
						}
					}
					
					// Write ticket JSON
					
					$json_out[] = $ticket_json;
					unset($ticket_json);
					
					// Messages
					
					$sql_messages = <<< SQL
SELECT m.id AS message_id, m.created_date, m.is_outgoing, m.worker_id, m.response_time, m.hash_header_message_id, 
(SELECT email FROM address WHERE id = m.address_id) as sender, 
(SELECT headers FROM message_headers WHERE message_id = m.id) as headers, 
(SELECT data FROM storage_message_content WHERE chunk = 1 AND id = m.id) as content, 
(SELECT GROUP_CONCAT(attachment_id) FROM attachment_link WHERE context = 'cerberusweb.contexts.message' AND context_id = m.id) AS attachment_ids 
FROM message m 
WHERE ticket_id = $ticket_id
SQL;
					
					$stmt_msgs = $db->prepare($sql_messages);
					$stmt_msgs->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
					$stmt_msgs->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 100);
					
					if($stmt_msgs->execute()) {
						$stmt_msgs->bind_result(
							$message_id,
							$created_date,
							$is_outgoing,
							$worker_id,
							$response_time,
							$hash_header_message_id,
							$sender,
							$headers,
							$content,
							$attachment_ids
						);
						
						while($stmt_msgs->fetch()) {
							$message_uid = sprintf("message_%d", $message_id);
							$html_message_uid = null;
							
							// Attachments
							if(!empty($attachment_ids)) {
								$sql_attachments = sprintf("SELECT id, name, mime_type, storage_size, storage_key, storage_extension FROM attachment WHERE id IN (%s)", $attachment_ids);
								$res = $db->query($sql_attachments);
								
								if($res && $res instanceof \mysqli_result)
								while($row = $res->fetch_assoc()) {
									$file_path = $storage_path . 'attachments/' . $row['storage_key'];
									
									if(file_exists($file_path) && is_readable($file_path)) {
										$attachment_uid = sprintf("attachment_%d", $row['id']);
										
										if('original_message.html' == $row['name'])
											$html_message_uid = $attachment_uid;
										
										$attachment_json = [
											'uid' => $attachment_uid,
											'_context' => 'attachment',
											'name' => @$row['name'] ?: 'untitled',
											'mime_type' => $row['mime_type'],
											'attach' => [
												'message:{{{uid.' . $message_uid . '}}}',
											],
											'content' => sprintf('data:%s;base64,', $row['mime_type']) . base64_encode(file_get_contents($file_path)),
										];
										
										$json_out[] = $attachment_json;
										unset($attachment_json);
									}
								}
								
								$res->close();
							}
							
							$message_json = [
								'uid' => $message_uid,
								'_context' => 'message',
								'ticket_id' => '{{{uid.' . $ticket_uid . '}}}',
								'created' => $created_date,
								'is_outgoing' => $is_outgoing ? 1 : 0,
								'sender' => $sender,
								'response_time' => $response_time,
								'hash_header_message_id' => $hash_header_message_id,
								'headers' => $headers,
								'content' => $content ? mb_convert_encoding($content, 'utf-8') : ' ',
							];
							
							if($is_outgoing) {
								if(false != ($new_worker_id = $this->mapWorkerId($worker_id)))
									$message_json['worker_id'] = $new_worker_id;
							}
							
							if($html_message_uid)
								$message_json['html_attachment_id'] = '{{{uid.' . $html_message_uid . '}}}';
							
							$json_out[] = $message_json;
							unset($message_json);
						}
						
						$stmt_msgs->close();
					}
					
					// Comments
					
					if(!empty($comment_ids)) {
						$sql_comments = sprintf("SELECT id, created, comment, owner_context_id AS worker_id FROM comment WHERE id IN (%s) AND owner_context = 'cerberusweb.contexts.worker'", $comment_ids);
						$res = $db->query($sql_comments);
						
						if($res && $res instanceof \mysqli_result && $res->num_rows)
						while($row = $res->fetch_assoc()) {
							if(false == ($new_worker_id = $this->mapWorkerId($row['worker_id'])))
								continue;
							
							$comment_json = [
								'uid' => sprintf('comment_%d', $row['id']),
								'_context' => 'comment',
								'created' => $row['created'],
								'target__context' => 'ticket',
								'target_id' => '{{{uid.' . $ticket_uid . '}}}',
								'author__context' => 'worker',
								'author_id' => $new_worker_id,
								'comment' => $row['comment'],
							];
							$json_out[] = $comment_json;
						}
					}
					
					// Time Tracking
					
					$sql_timetracking = sprintf("SELECT id, time_actual_mins, log_date, worker_id, activity_id, is_closed, ".
						"(SELECT group_concat(comment.id) FROM comment WHERE context = 'cerberusweb.contexts.timetracking' AND context_id = timetracking_entry.id AND owner_context = 'cerberusweb.contexts.worker') AS comment_ids ".
						"FROM timetracking_entry ".
						"INNER JOIN context_link ON (".
							"context_link.to_context = 'cerberusweb.contexts.timetracking' ".
							"AND context_link.to_context_id = timetracking_entry.id ".
							"AND from_context = 'cerberusweb.contexts.ticket' ".
							"AND from_context_id = %d".
						")",
						$ticket_id
					);
					
					$res = $db->query($sql_timetracking);
					
					if($res && $res instanceof \mysqli_result && $res->num_rows)
					while($row = $res->fetch_assoc()) {
						if(false == ($new_worker_id = $this->mapWorkerId($row['worker_id'])))
							continue;
						
						$time_uid = sprintf('timetracking_%d', $row['id']);
						
						$timetracking_json = [
							'uid' => $time_uid,
							'_context' => 'time_entry',
							'log_date' => $row['log_date'],
							'is_closed' => $row['is_closed'] ? 1 : 0,
							'mins' => intval($row['time_actual_mins']),
							'activity_id' => $this->mapTimeTrackingActivityId($row['activity_id']),
							'worker_id' => $new_worker_id,
							'links' => [
								'ticket:' . '{{{uid.' . $ticket_uid . '}}}',
							],
						];
						$json_out[] = $timetracking_json;
						
						$comment_ids = $row['comment_ids'];
						
						// Time tracking comments
						if(!empty($comment_ids)) {
							$sql_comments = sprintf("SELECT id, created, comment, owner_context_id AS worker_id FROM comment WHERE id IN (%s) AND owner_context = 'cerberusweb.contexts.worker'", $comment_ids);
							$res = $db->query($sql_comments);
							
							if($res && $res instanceof \mysqli_result && $res->num_rows)
							while($row = $res->fetch_assoc()) {
								if(false == ($new_worker_id = $this->mapWorkerId($row['worker_id'])))
									continue;
								
								$comment_json = [
									'uid' => sprintf('comment_%d', $row['id']),
									'_context' => 'comment',
									'created' => $row['created'],
									'target__context' => 'time_entry',
									'target_id' => '{{{uid.' . $time_uid . '}}}',
									'author__context' => 'worker',
									'author_id' => $new_worker_id,
									'comment' => $row['comment'],
								];
								$json_out[] = $comment_json;
							}
						}
					}
					
					// Package
					
					$package_json = [
						'package' => [
							'name' => sprintf('Ticket #%d', $ticket_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '9.1.1',
								'plugins' => [],
							],
							'configure' => [
								'prompts' => [],
								'placeholders' => [],
								'options' => [
									'disable_events' => true,
								],
							],
						],
						'records' => $json_out
					];
					
					unset($json_out);
					
					//$doc->asXML(sprintf("%s%09d.xml", $dir, $ticket_id));
					echo sprintf("Writing %s%09d.json\n", $dir, $ticket_id);
					
					file_put_contents(sprintf("%s%09d.json", $dir, $ticket_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
				
				$stmt->close();
			}
		}
		
		function export() {
			//$this->_exportWorkers();
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
			'test',
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
		if(false == (@$exporter_options = $config['exporter']['options']) || !is_array($exporter_options))
			die("[ERROR] Config: ['exporter']['options'] is required.\n");
		
		if(false == ($exporter = new $exporter_class($exporter_options)))
			die("[ERROR] Failed to load the exporter class.\n");
		
		self::$_exporter = $exporter;
		self::$_options = array(
			'output_dir' => $output_dir,
		);
		
		if(array_key_exists('test', $options)) {
			if(true === self::$_exporter->testConfig($exporter_options)) {
				echo "OK\n";
			}
			exit;
		}
		
		return true;
	}
	
	static private function _printHelp() {
		echo 'Usage: php ' . basename(__FILE__) . ' -c <config.json> [options]' . PHP_EOL;
		
		echo <<< EOF
--help
	Show available options.
-c, --config <file>
	The configuration file to use.
-o, --output <dir>
	The output directory for writing the export.
--test
	Test the configuration.

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

// [TODO] Register callbacks for ID mapping

CerbImpEx::export();
}