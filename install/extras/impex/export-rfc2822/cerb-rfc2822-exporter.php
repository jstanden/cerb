<?php
namespace Cerb\Impex\Exporters {
	use Cerb\Impex\CerbImpex;
	require('vendor/autoload.php');
	
	if(!extension_loaded('imap'))
		die("The `imap` extension is required.");
	
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
		
		static function convertEncoding($text, $charset=null) {
			$has_iconv = extension_loaded('iconv') ? true : false;
			$charset = mb_strtolower($charset);
			
			// Otherwise, fall back to mbstring's auto-detection
			mb_detect_order('iso-2022-jp-ms, iso-2022-jp, utf-8, iso-8859-1, windows-1252');
			
			// Normalize charsets
			switch($charset) {
				case 'us-ascii':
					$charset = 'ascii';
					break;
					
				case 'win-1252':
					$charset = 'windows-1252';
					break;
					
				case 'ks_c_5601-1987':
				case 'ks_c_5601-1992':
				case 'ks_c_5601-1998':
				case 'ks_c_5601-2002':
					$charset = 'cp949';
					break;
					
				case NULL:
					$charset = mb_detect_encoding($text);
					break;
			}
			
			// If we're starting with Windows-1252, convert some special characters
			if(0 == strcasecmp($charset, 'windows-1252')) {
			
				// http://www.toao.net/48-replacing-smart-quotes-and-em-dashes-in-mysql
				$text = str_replace(
					array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
					array("'", "'", '"', '"', '-', '--', '...'),
					$text
				);
			}
			
			// If we can use iconv, do so first
			if($has_iconv && false !== ($out = @iconv($charset, 'utf-8' . '//TRANSLIT//IGNORE', $text))) {
				return $out;
			}
			
			// Otherwise, try mbstring
			if(@mb_check_encoding($text, $charset)) {
				if(false !== ($out = mb_convert_encoding($text, 'utf-8', $charset)))
					return $out;
				
				// Try with the internal charset
				if(false !== ($out = mb_convert_encoding($text, 'utf-8')))
					return $out;
			}
			
			return $text;
		}
		
		static function fixQuotePrintableArray($input, $encoding=null) {
			array_walk_recursive($input, function(&$v, $k) {
				if(!is_string($v))
					return;
				
				$v = self::fixQuotePrintableString($v);
			});
			
			return $input;
		}
		
		static function fixQuotePrintableString($input, $encoding=null) {
			$out = '';
			
			// Make a single element array from any !array input
			if(!is_array($input))
				$input = array($input);
	
			if(is_array($input))
			foreach($input as $str) {
				$out .= !empty($out) ? ' ' : '';
				$parts = imap_mime_header_decode($str);
				
				if(is_array($parts))
				foreach($parts as $part) {
					try {
						$charset = ($part->charset != 'default') ? $part->charset : $encoding;
						$out .= self::convertEncoding($part->text, $charset);
						
					} catch(\Exception $e) {}
				}
			}
	
			// Strip invalid characters in our encoding
			if(!mb_check_encoding($out, 'utf-8'))
				$out = mb_convert_encoding($out, 'utf-8', 'utf-8');
			
			return $out;
		}
		
		static function parseHeaders($raw_headers, $flatten_arrays=true, $convert_qp=true) {
			if(false == ($mime = new \MimeMessage('var', $raw_headers)))
				return false;
			
			if(!isset($mime->data))
				return false;
			
			if($convert_qp) {
				$headers = self::fixQuotePrintableArray($mime->data['headers']);
			} else {
				$headers = $mime->data['headers'];
			}
			
			if($flatten_arrays)
			foreach($headers as &$v) {
				if(is_array($v))
					$v = implode(';; ', $v);
			}
			
			ksort($headers);
			
			return $headers;
		}
		
		private function _getDatabase() {
			if(is_null($this->_db)) {
				$this->_db = mysqli_connect($this->_config['db_host'], $this->_config['db_user'], $this->_config['db_pass'], $this->_config['db_name']);
				mysqli_query($this->_db, 'SET group_concat_max_len = 4096000');
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
				
			return true;
		}
		
		private function _exportTickets() {
			$db = $this->_getDatabase();
			
			//$mask_prefix = $this->_config['mask_prefix'] ?? null;
			
			if(false == (@$storage_path = $this->_config['storage_path']))
				die("The 'storage_path' configuration setting is required.\n");
			
			// Sanitize the path
			$storage_path = rtrim($storage_path, '\\/') . '/';
			
			if(!file_exists($storage_path))
				die(sprintf("The 'storage_path' (%s) doesn't exist.\n", $storage_path));
			
			$sql = <<< SQL
SELECT t.id, t.mask, t.subject, t.status_id, t.importance, t.created_date, t.updated_date, t.group_id, t.bucket_id, g.name, b.name, 
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
			
			$statuses = [
				0 => 'open',
				1 => 'waiting',
				2 => 'closed',
				3 => 'deleted',
			];
			
			if($stmt->execute()) {
				$stmt->bind_result($ticket_id, $mask, $subject, $status_id, $importance, $created_date, $updated_date, $group_id, $bucket_id, $group_name, $bucket_name, $org_name, $participants, $comment_ids);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-tickets-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					// Messages
					
					$sql_messages = <<< SQL
SELECT m.id AS message_id, m.created_date, m.is_outgoing, m.response_time, m.hash_header_message_id, 
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
						$stmt_msgs->bind_result($message_id, $created_date, $is_outgoing, $response_time, $hash_header_message_id, $sender, $raw_headers, $content, $attachment_ids);
						
						while($stmt_msgs->fetch()) {
							$message = new \Swift_Message();
							$message->getHeaders()->removeAll('Message-ID');
							
							$headers = self::parseHeaders($raw_headers);
							
							$addys_to = @imap_rfc822_parse_adrlist($headers['to'], 'localhost');
							$addys_from = @imap_rfc822_parse_adrlist($headers['from'], 'localhost');
							@imap_errors();
							
							if(!empty($addys_to)) {
								$addys = [];
								
								foreach($addys_to as $addy_model) {
									$addy = sprintf("%s@%s", $addy_model->mailbox, $addy_model->host);
									$personal = @$addy_model->personal ?: null;
									
									$addys[$addy] = $personal;
								}
								
								try {
									$message->setTo($addys);
									
								} catch (\Exception $e) {
									$message->setTo('noreply@localhost');
								}
							} else {
								$message->setTo('noreply@localhost');
							}
							
							if(!empty($addys_from)) {
								$addys = [];
								
								foreach($addys_from as $addy_model) {
									$addy = sprintf("%s@%s", $addy_model->mailbox, $addy_model->host);
									$personal = @$addy_model->personal ?: null;
									
									$addys[$addy] = $personal;
								}
								
								try {
									$message->setFrom($addys);
								} catch (\Exception $e) {
									$message->setFrom('noreply@localhost');
								}
							} else {
								$message->setFrom('noreply@localhost');
							}
							
							if(array_key_exists('message-id', $headers)) {
								try {
									$message->getHeaders()->addIdHeader('Message-ID', trim($headers['message-id'], '<>'));
								} catch(\Exception $e) {
									$message->generateId();
								}
							} else {
								$message->generateId();
							}
							
							if(array_key_exists('in-reply-to', $headers)) {
								try {
									$message->getHeaders()->addIdHeader('In-Reply-To', trim($headers['in-reply-to'], '<>'));
								} catch(\Exception $e) {
									
								}
							}
							
							$message->setSubject($subject);
							$message->setBody($content ? mb_convert_encoding($content, 'utf-8') : ' ');
							$message->setDate($created_date);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Id', $ticket_id);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Mask', $mask);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Status', $statuses[$status_id]);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Importance', $importance);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Org', $org_name);
							$message->getHeaders()->addTextHeader('X-Cerb-Ticket-Participants', $participants);
							$message->getHeaders()->addTextHeader('X-Cerb-Message-Id', $message_id);
							$message->getHeaders()->addTextHeader('X-Cerb-Message-Response-Time', $response_time);
							$message->getHeaders()->addTextHeader('X-Cerb-Group-Id', $group_id);
							$message->getHeaders()->addTextHeader('X-Cerb-Group-Name', $group_name);
							$message->getHeaders()->addTextHeader('X-Cerb-Bucket-Id', $bucket_id);
							$message->getHeaders()->addTextHeader('X-Cerb-Bucket-Name', $bucket_name);
							
							// Attachments
							if(!empty($attachment_ids)) {
								$sql_attachments = sprintf("SELECT id, name, mime_type, storage_size, storage_key, storage_extension FROM attachment WHERE id IN (%s)", $attachment_ids);
								$res = $db->query($sql_attachments);
								
								if($res && $res instanceof \mysqli_result)
								while($row = $res->fetch_assoc()) {
									$file_path = $storage_path . 'attachments/' . $row['storage_key'];
									
									if(file_exists($file_path) && is_readable($file_path)) {
										//$attachment_uid = sprintf("attachment_%d", $row['id']);
										
										if('original_message.html' == $row['name']) {
											//$html_message_uid = $attachment_uid;
											$message->addPart(file_get_contents($file_path), 'text/html');
											
										} else {
											//$message->addPart(file_get_contents($file_path), 'text/html');
											$message
												->attach(
													\Swift_Attachment::fromPath($file_path, $row['mime_type'])
													->setFilename($row['name'])
												);
										}
									}
								}
								
								$res->close();
							}
						}
						
						$stmt_msgs->close();
					}
					
					try {
						/*
						$mime = $message->toString();
						echo "From MAILER-DAEMON " . date('r', $created_date) . "\r\n";
						echo $mime;
						echo "\r\n";
						*/
						
						echo sprintf("Writing %s%09d.msg\n", $dir, $ticket_id);
						
						$mime = $message->toString();
						
						file_put_contents(sprintf("%s%09d.msg", $dir, $ticket_id), $mime);
						
					} catch(\Exception $e) {
						
					}
				}
				
				$stmt->close();
			}
		}
		
		function export() {
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