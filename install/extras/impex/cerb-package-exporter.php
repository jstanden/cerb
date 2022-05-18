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
	
	class Cerb10 extends Exporter {
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
		
		function mapSenderAddressId($id) {
			return $id;
			
			/*
			$id = intval($id);
			
			$map = [
				2 => 1, // mailbox@example.com
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return 0;
			*/
		}
		
		function mapEmailSigIds($sig_id) {
			return $sig_id;
			
			/*
			$map = [
				2 => 1, // Field
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return 0;
			*/
		}
		
		function mapEmailTemplateIds($tpl_id) {
			return $tpl_id;
			
			/*
			$map = [
				2 => 1, // Field
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return 0;
			*/
		}
		
		function mapGroupBucketIds($group_id, $bucket_id) {
			$group_id = intval($group_id);
			$bucket_id = intval($bucket_id);
			
			return [
				$group_id,
				$bucket_id,
			];
			
			/*
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
			*/
		}
		
		function mapCustomFieldset($id) {
			$id = intval($id);
			
			return $id;
			
			/*
			$map = [
				2 => 1, // Field
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return null;
			*/
		}
		
		function mapCustomFieldId($id) {
			$id = intval($id);
			
			return $id;
			
			/*
			$map = [
				2 => 1, // Field
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return null;
			*/
		}
		
		function mapOwnerIds($context, $context_id) {
			switch($context) {
				case 'cerberusweb.contexts.worker':
				case 'worker':
					$worker_id = $this->mapWorkerId($context_id);
					
					return [
						$context,
						$worker_id,
					];
				
				case 'cerberusweb.contexts.group':
				case 'group':
					list($group_id,) = $this->mapGroupBucketIds($context_id, 0);
					
					return [
						$context,
						$group_id,
					];
					
				case 'cerberusweb.contexts.role':
				case 'role':
					return [
						'cerberusweb.contexts.app',
						0,
					];
					
				default:
					return [
						'cerberusweb.contexts.app',
						0,
					];
			}
		}
		
		function mapWorkerId($id) {
			$id = intval($id);
			return $id;
			
			/*
			$map = [
				1 => 2, // Kina
				2 => 3, // Milo
				10 => 1, // Jeff
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return null;
			*/
		}
		
		function mapMailTransportId($id) {
			return $id;
		}
		
		function mapTimeTrackingActivityId($id) {
			$id = intval($id);
			
			return $id;
			
			/*
			$map = [
				1 => 1, // Development
				2 => 2, // Troubleshooting
				3 => 3, // Consulting
				4 => 4, // Sales
			];
			
			if(array_key_exists($id, $map))
				return $map[$id];
			
			return 0;
			*/
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
				die("[ERROR] The 'mysqli' PHP extension is required.");

			if(!extension_loaded('mbstring'))
				die("[ERROR] The 'mbstring' PHP extension is required.");
			
			// Test the MySQL connection
			$db = mysqli_connect($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
			
			if(false == $db)
				die("[ERROR] Can't connect to the given database.\n");
				
			// [TODO] Check the table schemas + Cerb version
			
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
			
			$sql = "SELECT id, first_name, last_name, title, at_mention_name, dob, gender, is_superuser, is_disabled, is_mfa_required, is_password_disabled, language, location, mobile, phone, time_format, timezone, ".
				"(SELECT email FROM address WHERE id = worker.email_id) AS email, ".
				"(SELECT storage_key FROM context_avatar WHERE context = 'cerberusweb.contexts.worker' AND context_id = worker.id) AS image_storage_key ".
				"FROM worker ".
				"WHERE 1"
			;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($id, $first_name, $last_name, $title, $at_mention_name, $dob, $gender, $is_superuser, $is_disabled, $is_mfa_required, $is_password_disabled, $language, $location, $mobile, $phone, $time_format, $timezone, $email, $image_storage_key);
				
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
					
					if($at_mention_name)
						$worker_json['at_mention_name'] = $at_mention_name;
					
					if($dob)
						$worker_json['dob'] = $dob;
					
					if($gender)
						$worker_json['gender'] = $gender;
					
					if($is_superuser)
						$worker_json['is_superuser'] = 1;
					
					if($is_disabled)
						$worker_json['is_disabled'] = 1;
					
					if($is_mfa_required)
						$worker_json['is_mfa_required'] = 1;
					
					if($is_password_disabled)
						$worker_json['is_password_disabled'] = 1;
					
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
								'cerb_version' => '10.2.0',
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
		
		function mapRecordFieldKeys($fields, $context) {
			if(!is_array($fields))
				return $fields;
			
			foreach($fields as $field_idx => $field_key) {
				if('cf_' == substr($field_key,0, 3)) {
					$field_id = substr($field_key, 3);
					$fields[$field_idx] = 'cf_' . $this->mapCustomFieldId($field_id);
				} else if('custom_' == substr($field_key,0, 7)) {
					$field_id = substr($field_key, 7);
					$fields[$field_idx] = 'custom_' . $this->mapCustomFieldId($field_id);
				}
			}
			
			return $fields;
		}
		
		function mapWorklistParams($params, $context) {
			if($context != 'cerberusweb.contexts.ticket')
				return $params;
			
			foreach($params as &$param) {
				// [TODO] Arrays/groups
				if(!is_array($param) || !array_key_exists('field', $param))
					continue;
				
				switch($param['field']) {
					case 't_bucket_id':
						if($param['operator'] == 'in') {
							$param['value'] = array_map(function($bucket_id) {
								list(,$bucket_id) = $this->mapGroupBucketIds(0, $bucket_id);
								return $bucket_id;
							}, $param['value']);
						} else if($param['operator'] == '=') {
							if(is_numeric($param['value'])) {
								list(, $bucket_id) = $this->mapGroupBucketIds(0, $param['value']);
								$param['value'] = $bucket_id;
							}
						}
						break;
					
					case 't_group_id':
						if($param['operator'] == 'in') {
							$param['value'] = array_map(function($group_id) {
								list($group_id,) = $this->mapGroupBucketIds($group_id, 0);
								return $group_id;
							}, $param['value']);
						} else if($param['operator'] == '=') {
							if(is_numeric($param['value'])) {
								list($group_id,) = $this->mapGroupBucketIds($param['value'], 0);
								$param['value'] = $group_id;
							}
						}
						break;
					
					case 'm_worker_id':
					case 't_owner_id':
					case '*_groups_of_worker':
						if($param['operator'] == 'in') {
							$param['value'] = array_map(function($worker_id) {
								return $this->mapWorkerId($worker_id);
							}, $param['value']);
						} else if($param['operator'] == '=') {
							if(is_numeric($param['value']))
								$param['value'] = $this->mapWorkerId($param['value']);
						}
						break;
				}
			}
			
			return $params;
		}
		
		private function _exportWorkspaces() {
			$db = $this->_getDatabase();
			
			$sql = "SELECT id, name, owner_context, owner_context_id, extension_id, extension_params_json, updated_at ".
				"FROM workspace_page ".
				"WHERE 1"
			;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($workspace_page_id, $workspace_page_name, $workspace_page_owner_context, $workspace_page_owner_id, $workspace_page_extension_id, $workspace_page_extension_params_json, $workspace_page_updated_at);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '04-workspaces-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out_records = [];
					$json_out_bots = [];
					
					list($workspace_page_owner_context, $workspace_page_owner_id) = $this->mapOwnerIds($workspace_page_owner_context, $workspace_page_owner_id);
					
					$workspace_page_json = [
						'uid' => sprintf('workspace_page_%d', $workspace_page_id),
						'_context' => 'workspace_page',
						'name' => $workspace_page_name,
						'extension_id' => $workspace_page_extension_id,
						'extension_params' => json_decode($workspace_page_extension_params_json, true),
						'owner__context' => $workspace_page_owner_context,
						'owner_id' => $workspace_page_owner_id,
						'updated_at' => $workspace_page_updated_at,
						'tabs' => [],
					];
					
					// Tabs
					
					$sql = "SELECT id, name, pos, extension_id, params_json, updated_at ".
						"FROM workspace_tab ".
						"WHERE workspace_page_id = $workspace_page_id"
					;
					
					$stmt_workspace_tab = $db->prepare($sql);
					$stmt_workspace_tab->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
					
					if($stmt_workspace_tab->execute()) {
						$stmt_workspace_tab->bind_result(
							$workspace_tab_id,
							$workspace_tab_name,
							$workspace_tab_pos,
							$workspace_tab_extension_id,
							$workspace_tab_extension_params_json,
							$workspace_tab_updated_at
						);
						
						while($stmt_workspace_tab->fetch()) {
							$workspace_tab_json = [
								'uid' => sprintf('workspace_tab_%d', $workspace_tab_id),
								'_context' => 'workspace_tab',
								'name' => $workspace_tab_name,
								'extension_id' => $workspace_tab_extension_id,
								'params' => json_decode($workspace_tab_extension_params_json, true),
								'pos' => $workspace_tab_pos,
								'updated_at' => $workspace_tab_updated_at,
								'worklists' => [],
								'widgets' => [],
							];
							
							// Widgets
							
							$sql = "SELECT id, label, pos, extension_id, params_json, width_units, zone, updated_at ".
								"FROM workspace_widget ".
								"WHERE workspace_tab_id = $workspace_tab_id"
							;
							
							if(false == ($stmt_workspace_widget = $db->prepare($sql))) {
								echo "ERROR: " . $sql;
								
							} else {
								$stmt_workspace_widget->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
								
								if($stmt_workspace_widget->execute()) {
									$stmt_workspace_widget->bind_result($workspace_widget_id, $workspace_widget_label, $workspace_widget_pos, $workspace_widget_extension_id, $workspace_widget_params_json, $workspace_widget_width_units, $workspace_widget_zone, $workspace_widget_updated_at);
									
									while($stmt_workspace_widget->fetch()) {
										$widget_params = json_decode($workspace_widget_params_json, true);
										
										// Convert nested worklist models
										if(is_array($widget_params)) {
											if (array_key_exists('series', $widget_params)) {
												foreach($widget_params['series'] as $series_idx => $series) {
													if (array_key_exists('worklist_model', $series) && array_key_exists('context', $series['worklist_model'])) {
														if (array_key_exists('columns', $series['worklist_model'])) {
															$widget_params['series'][$series_idx]['worklist_model']['columns'] = $this->mapRecordFieldKeys($series['worklist_model']['columns'],$series['worklist_model']['context']);
														}
														if (array_key_exists('params', $series['worklist_model'])) {
															$widget_params['series'][$series_idx]['worklist_model']['params'] = $this->mapWorklistParams($series['worklist_model']['params'], $series['worklist_model']['context']);
														}
													}
												}
											}
											
											if (array_key_exists('worklist_model', $widget_params) && array_key_exists('context', $widget_params['worklist_model'])) {
												if (array_key_exists('columns', $widget_params['worklist_model'])) {
													$widget_params['worklist_model']['columns'] = $this->mapRecordFieldKeys($widget_params['worklist_model']['columns'], $widget_params['worklist_model']['context']);
												}
												if (array_key_exists('params', $widget_params['worklist_model'])) {
													$widget_params['worklist_model']['params'] = $this->mapWorklistParams($widget_params['worklist_model']['params'], $widget_params['worklist_model']['context']);
												}
											}
										}
										
										$workspace_widget_json = [
											'uid' => sprintf('workspace_widget_%d', $workspace_widget_id),
											'_context' => 'workspace_widget',
											'label' => $workspace_widget_label,
											'extension_id' => $workspace_widget_extension_id,
											'params' => $widget_params,
											'pos' => $workspace_widget_pos,
											'width_units' => $workspace_widget_width_units,
											'zone' => $workspace_widget_zone,
										];
										
										$workspace_tab_json['widgets'][] = $workspace_widget_json;
									}
								}
								
								$stmt_workspace_widget->close();
							}
							
							// Lists
							
							$sql = "SELECT id, name, workspace_tab_pos, context, updated_at, options_json, columns_json, params_editable_json, params_required_json, params_required_query, render_limit, render_subtotals, render_sort_json ".
								"FROM workspace_list ".
								"WHERE workspace_tab_id = $workspace_tab_id"
							;
							
							if(false == ($stmt_workspace_list = $db->prepare($sql))) {
								echo "ERROR: " . $sql;
								
							} else {
								$stmt_workspace_list->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
								
								if($stmt_workspace_list->execute()) {
									$stmt_workspace_list->bind_result(
										$workspace_list_id,
										$workspace_list_name,
										$workspace_list_workspace_tab_pos,
										$workspace_list_context,
										$workspace_list_updated_at,
										$workspace_list_options_json,
										$workspace_list_columns_json,
										$workspace_list_params_json,
										$workspace_list_params_required_json,
										$workspace_list_params_required_query,
										$workspace_list_render_limit,
										$workspace_list_render_subtotals,
										$workspace_list_render_sort_json // [TODO]
									);
									
									while($stmt_workspace_list->fetch()) {
										$workspace_list_json = [
											'pos' => $workspace_list_workspace_tab_pos,
											'title' => $workspace_list_name,
											'model' => [
												'options' => json_decode($workspace_list_options_json, true),
												'columns' => $this->mapRecordFieldKeys(json_decode($workspace_list_columns_json, true), $workspace_list_context),
												'params' => $this->mapWorklistParams(json_decode($workspace_list_params_json, true), $workspace_list_context),
												'params_required' => $this->mapWorklistParams(json_decode($workspace_list_params_required_json, true), $workspace_list_context),
												'params_required_query' => $workspace_list_params_required_query,
												'limit' => $workspace_list_render_limit,
												'subtotals' => $workspace_list_render_subtotals,
												'sort_asc' => [],
												'sort_by' => [],
												'context' => $workspace_list_context,
											],
										];
										
										$workspace_tab_json['worklists'][] = $workspace_list_json;
									}
								}
								
								$stmt_workspace_list->close();
							}
						}
						
						$workspace_page_json['tabs'][] = $workspace_tab_json;
					}
					
					$json_out_workspaces = [$workspace_page_json];
					
					$stmt_workspace_tab->close();
					
					$package_json = [
						'package' => [
							'name' => sprintf('Workspace Page #%d', $workspace_page_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '10.2.0',
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
						'records' => $json_out_records,
						'bots' => $json_out_bots,
						'workspaces' => $json_out_workspaces,
					];
					
					echo sprintf("Writing %s%09d.json\n", $dir, $workspace_page_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $workspace_page_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportSenderAddresses() {
			$db = $this->_getDatabase();
			
			$sql =  <<< SQL
SELECT id, email, host, is_banned, is_defunct, created_at, updated, mail_transport_id
FROM address
WHERE mail_transport_id > 0
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($address_id, $address_email, $address_host, $address_is_banned, $address_is_defunct, $address_created_at, $address_updated, $address_mail_transport_id);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-sender-addresses-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					$address_mail_transport_id = $this->mapMailTransportId($address_mail_transport_id);
					
					$address_json = [
						'uid' => sprintf('address_%d', $address_id),
						'_context' => 'address',
						'email' => $address_email,
						'host' => $address_host,
						'is_banned' => $address_is_banned ? 1 : 0,
						'is_defunct' => $address_is_defunct ? 1 : 0,
						'created_at' => $address_created_at,
						'updated' => $address_updated,
						'mail_transport_id' => $address_mail_transport_id,
					];
					
					$json_out[] = $address_json;
					
					$package_json = [
						'package' => [
							'name' => sprintf('Address #%d', $address_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '9.3.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $address_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $address_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportEmailSignatures() {
			$db = $this->_getDatabase();
			
			$sql =  <<< SQL
SELECT id, name, signature, signature_html, is_default, updated_at
FROM email_signature
WHERE 1
ORDER BY id
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($sig_id, $sig_name, $sig_text, $sig_html, $sig_is_default, $sig_updated_at);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-email-sigs-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					//$address_mail_transport_id = $this->mapMailTransportId($address_mail_transport_id);
					
					$sig_json = [
						'uid' => sprintf('sig_%d', $sig_id),
						'_context' => 'email_signature',
						'name' => $sig_name,
						'signature' => $sig_text,
						'signature_html' => $sig_html,
						'is_default' => $sig_is_default,
						'updated_at' => $sig_updated_at,
						'owner__context' => 'app',
						'owner_id' => 0,
					];
					
					$json_out[] = $sig_json;
					
					$package_json = [
						'package' => [
							'name' => sprintf('Signature #%d', $sig_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '10.2.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $sig_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $sig_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportEmailTemplates() {
			$db = $this->_getDatabase();
			
			$sql =  <<< SQL
SELECT id, name, updated_at, content, signature
FROM mail_html_template
WHERE 1
ORDER BY id
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($tpl_id, $tpl_name, $tpl_updated_at, $tpl_content, $tpl_signature);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-email-templates-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					$tpl_json = [
						'uid' => sprintf('template_%d', $tpl_id),
						'_context' => 'html_template',
						'name' => $tpl_name,
						'owner__context' => 'app',
						'owner_id' => 0,
						'signature' => $tpl_signature,
						'content' => $tpl_content,
						'updated_at' => $tpl_updated_at,
					];
					
					$json_out[] = $tpl_json;
					
					$package_json = [
						'package' => [
							'name' => sprintf('Template #%d', $tpl_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '10.2.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $tpl_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $tpl_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportCustomFieldsets() {
			$db = $this->_getDatabase();
			
			$sql =  <<< SQL
SELECT id, name, context, updated_at, owner_context, owner_context_id
FROM custom_fieldset
WHERE 1
ORDER BY id
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($cfset_id, $cfset_name, $cfset_context, $cfset_updated_at, $cfset_owner_context, $cfset_owner_id);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-custom-fieldsets-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					list($cfset_owner_context, $cfset_owner_id) = $this->mapOwnerIds($cfset_owner_context, $cfset_owner_id);
					
					$cfset_json = [
						'uid' => sprintf('cfieldset_%d', $cfset_id),
						'_context' => 'custom_fieldset',
						'context' => $cfset_context,
						'name' => $cfset_name,
						'owner__context' => $cfset_owner_context,
						'owner_id' => $cfset_owner_id,
						'updated_at' => $cfset_updated_at,
					];
					
					$json_out[] = $cfset_json;
					
					
					
					$sql =  <<< SQL
SELECT id, name, updated_at, type, pos, context, params_json
FROM custom_field
WHERE 1
AND custom_fieldset_id = $cfset_id
ORDER BY id
SQL;
					
					$stmt_cfields = $db->prepare($sql);
					$stmt_cfields->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
					
					if($stmt_cfields->execute()) {
						$stmt_cfields->bind_result($cf_id, $cf_name, $cf_updated_at, $cf_type, $cf_pos, $cf_context, $cf_params_json);
						
						while($stmt_cfields->fetch()) {
							$cf_json = [
								'uid' => sprintf('cfield_%d', $cf_id),
								'_context' => 'custom_field',
								'context' => $cf_context,
								'custom_fieldset_id' => '{{{uid.cfieldset_' . $cfset_id . '}}}',
								'name' => $cf_name,
								'params' => json_decode($cf_params_json, true),
								'pos' => $cf_pos,
								'type' => $cf_type,
								'updated_at' => $cf_updated_at,
							];
							
							$json_out[] = $cf_json;
						}
					}
					
					$stmt_cfields->close();
					
					$package_json = [
						'package' => [
							'name' => sprintf('Custom Fieldset #%d', $cfset_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '10.2.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $cfset_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $cfset_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportCustomFields() {
			$db = $this->_getDatabase();
			
			$sql =  <<< SQL
SELECT id, name, updated_at, type, pos, context, params_json
FROM custom_field
WHERE 1
AND custom_fieldset_id = 0
ORDER BY id
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($cf_id, $cf_name, $cf_updated_at, $cf_type, $cf_pos, $cf_context, $cf_params_json);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '02-custom-fields-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					$cf_json = [
						'uid' => sprintf('cfield_%d', $cf_id),
						'_context' => 'custom_field',
						'context' => $cf_context,
						'name' => $cf_name,
						'params' => json_decode($cf_params_json, true),
						'pos' => $cf_pos,
						'type' => $cf_type,
						'updated_at' => $cf_updated_at,
					];
					
					$json_out[] = $cf_json;
					
					$package_json = [
						'package' => [
							'name' => sprintf('Custom Field #%d', $cf_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '10.2.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $cf_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $cf_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportGroups() {
			$db = $this->_getDatabase();
			
			if(false == (@$storage_path = $this->_config['storage_path']))
				die("The 'storage_path' configuration setting is required.\n");
			
			// Sanitize the path
			$storage_path = rtrim($storage_path, '\\/') . '/';
			
			if(!file_exists($storage_path))
				die(sprintf("The 'storage_path' (%s) doesn't exist.\n", $storage_path));
			
			$sql =  <<< SQL
SELECT id, name, is_private, reply_address_id, reply_personal, reply_signature_id, reply_html_template_id,
(SELECT GROUP_CONCAT(worker_id) FROM worker_to_group where group_id = worker_group.id and is_manager = 1) AS manager_ids,
(SELECT GROUP_CONCAT(worker_id) FROM worker_to_group where group_id = worker_group.id and is_manager = 0) AS member_ids,
(SELECT storage_key FROM context_avatar WHERE context = 'cerberusweb.contexts.group' AND context_id = worker_group.id) AS image_storage_key
FROM worker_group
ORDER BY id
SQL;
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($group_id, $group_name, $is_private, $group_reply_address_id, $group_reply_personal, $group_reply_signature_id, $group_reply_html_template_id, $group_manager_ids, $group_member_ids, $image_storage_key);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '03-groups-%06d/', ++$bins);
						if(!file_exists($dir))
							mkdir($dir, 0700, true);
					}
					
					$json_out = [];
					
					$group_reply_address_id = $this->mapSenderAddressId($group_reply_address_id);
					$group_reply_signature_id = $this->mapEmailSigIds($group_reply_signature_id);
					$group_reply_html_template_id = $this->mapEmailTemplateIds($group_reply_html_template_id);
					
					$manager_ids = array_filter(
						array_map(function($worker_id) {
							return $this->mapWorkerId($worker_id);
						}, explode(',',  $group_manager_ids)),
						function($worker_id) {
							return !empty($worker_id);
						}
					);
					
					$member_ids = array_filter(
						array_map(function($worker_id) {
							return $this->mapWorkerId($worker_id);
						}, explode(',',  $group_member_ids)),
						function($worker_id) {
							return !empty($worker_id);
						}
					);
					
					$group_json = [
						'uid' => sprintf('group_%d', $group_id),
						'_context' => 'group',
						'name' => $group_name,
						'is_private' => $is_private ? 1 : 0,
						'reply_address_id' => $group_reply_address_id,
						'reply_personal' => $group_reply_personal,
						'reply_signature_id' => $group_reply_signature_id,
						'reply_html_template_id' => $group_reply_html_template_id
					];
					
					$roster = [];
					
					if($manager_ids) {
						$roster['manager'] = implode(',', $manager_ids);
					}
					
					if($member_ids) {
						$roster['member'] = implode(',', $member_ids);
					}
					
					if($roster)
						$group_json['members'] = $roster;
					
					// Profile pictures from storage?
					if($image_storage_key) {
						$file_path = $storage_path . 'context_avatar/' . $image_storage_key;
						
						if(file_exists($file_path) && is_readable($file_path)) {
							$group_json['image'] = sprintf('data:%s;base64,', 'image/png') . base64_encode(file_get_contents($file_path));
						}
					}
					
					$json_out[] = $group_json;
					
					$sql_buckets = <<< SQL
SELECT id, name, is_default, reply_address_id, reply_personal, reply_signature_id, reply_html_template_id
FROM bucket
WHERE group_id = $group_id
ORDER BY id
SQL;
					
					$stmt_buckets = $db->prepare($sql_buckets);
					
					if($stmt_buckets->execute()) {
						$stmt_buckets->bind_result(
							$bucket_id,
							$bucket_name,
							$bucket_is_default,
							$bucket_reply_address_id,
							$bucket_reply_personal,
							$bucket_reply_signature_id,
							$bucket_reply_html_template_id
						);
						
						while ($stmt_buckets->fetch()) {
							$bucket_reply_address_id = $this->mapSenderAddressId($bucket_reply_address_id);
							$bucket_reply_signature_id = $this->mapEmailSigIds($bucket_reply_signature_id);
							$bucket_reply_html_template_id = $this->mapEmailTemplateIds($bucket_reply_html_template_id);
							
							$json_out[] = [
								'uid' => sprintf('bucket_%d', $bucket_id),
								'_context' => 'bucket',
								'name' => $bucket_name,
								'group_id' => sprintf("{{{uid.group_%d}}}", $group_id),
								'is_default' => $bucket_is_default ? 1 : 0,
								'reply_address_id' => $bucket_reply_address_id,
								'reply_personal' => $bucket_reply_personal,
								'reply_signature_id' => $bucket_reply_signature_id,
								'reply_html_template_id' => $bucket_reply_html_template_id
							];
						}
					}
					
					$package_json = [
						'package' => [
							'name' => sprintf('Group #%d', $group_id),
							'revision' => 1,
							'requires' => [
								'cerb_version' => '9.3.0',
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
					
					echo sprintf("Writing %s%09d.json\n", $dir, $group_id);
					file_put_contents(sprintf("%s%09d.json", $dir, $group_id), json_encode($package_json, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR));
				}
			}
			
			$stmt->close();
		}
		
		private function _exportOrgs() {
			$db = $this->_getDatabase();
			$sql = "SELECT id, name, street, city, province, postal, country, phone, website, created, updated FROM contact_org";
			
			$stmt = $db->prepare($sql);
			$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
			
			$count = 0;
			$bins = 0;
			
			if($stmt->execute()) {
				$stmt->bind_result($id, $name, $street, $city, $province, $postal, $country, $phone, $website, $created, $updated);
				
				while($stmt->fetch()) {
					if(0 == $count++ % 2000) {
						$dir = sprintf(CerbImpex::getOption('output_dir') . '04-orgs-%06d/', ++$bins);
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
								'cerb_version' => '10.2.0',
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
						$dir = sprintf(CerbImpex::getOption('output_dir') . '09-tickets-%06d/', ++$bins);
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
								'cerb_version' => '10.2.0',
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
//			$this->_exportWorkers();
//			$this->_exportSenderAddresses();
//			$this->_exportEmailSignatures();
//			$this->_exportEmailTemplates();
//			$this->_exportCustomFieldsets();
//			$this->_exportCustomFields();
//			$this->_exportGroups();
//			$this->_exportOrgs();
//			$this->_exportTickets();
//			$this->_exportWorkspaces();
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
