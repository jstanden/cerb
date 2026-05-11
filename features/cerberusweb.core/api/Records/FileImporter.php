<?php
namespace Cerb\Records;

use Cerb\Records\FileImporter\Mapping;
use CerberusContexts;
use DAO_Address;
use DAO_ContactOrg;
use DAO_Worker;
use DevblocksPlatform;
use DevblocksSearchCriteria;
use Exception_DevblocksValidationError;
use Extension_DevblocksContext;
use IDevblocksContextImport;
use Model_CustomField;

class FileImporter {
	private ?\Model_AutomationResource $_resource;
	private Extension_DevblocksContext $_context_ext;
	private array $_context_keys;
	private mixed $_fp;
	private string $_import_format;
	
	/**
	 * @throws Exception_DevblocksValidationError
	 */
	public function __construct(\Model_AutomationResource $resource, string $context) {
		$this->_fp = DevblocksPlatform::getTempFile();
		$this->_resource = $resource;
		
		if(!($this->_context_ext = Extension_DevblocksContext::get($context))) {
			throw new Exception_DevblocksValidationError("The import context is invalid.");
		}
		
		if(!($this->_context_ext instanceof IDevblocksContextImport)) {
			throw new Exception_DevblocksValidationError("The import context does not support import.");
		}
		
		$this->_context_keys = $this->_context_ext->importGetKeys();
		
		if (false === $this->_resource->getFileContents($this->_fp))
			throw new Exception_DevblocksValidationError("The import file could not be read.");
		
		$this->_import_format = $resource->mime_type === 'text/jsonl' ? 'jsonl' : 'csv';
	}
	
	public function getRecordExtension() : Extension_DevblocksContext {
		return $this->_context_ext;
	}
	
	public function getImportFormat() : string {
		return $this->_import_format;
	}
	
	public function getRecordKeys() : array {
		return $this->_context_keys;
	}
	
	public function getFileRecordOffsets(int $limit=0, bool $skip_csv_headings=false) : array {
		$line_offsets = [];
		
		if($this->_import_format == 'jsonl') {
			$iter = DevblocksPlatform::services()->file()->indexLines($this->_fp);
			
			if($limit) {
				while($limit-- && $iter->valid()) {
					$line_offsets[] = $iter->current();
					$iter->next();
				}
			} else {
				$line_offsets = iterator_to_array($iter);
			}
		} else {
			// Advance past the headings
			if($skip_csv_headings) fgets($this->_fp, 64_000);
			$iter = DevblocksPlatform::services()->file()->indexCsv($this->_fp);
			
			if($limit) {
				while($limit-- && $iter->valid()) {
					$line_offsets[] = $iter->current();
					$iter->next();
				}
			} else {
				$line_offsets = iterator_to_array($iter);
			}
		}
		
		return $line_offsets;
	}
	
	public function validate(Mapping $mapping, ?string &$error=null) : bool {
		if(!method_exists($this->_context_ext, 'importGetKeys')) {
			$error = "The import context does not support import validation.";
			return false;
		}
		
		$keys = $this->getRecordKeys();
		$fields = $mapping->getFields();
		$columns = $mapping->getColumns();
		
		// Ensure we have some mapping
		if(0 == count(array_filter($columns, fn($v) => strlen(strval($v))))) {
			$error = "No fields are mapped from the import file.";
			return false;
		}
		
		// If our required fields weren't provided
		foreach($fields as $field_idx => $field_key) {
			if(array_key_exists($field_key, $keys) && ($keys[$field_key]['required'] ?? false)) {
				if(!strlen(strval($columns[$field_idx] ?? ''))) {
					$error = sprintf("`%s` is required.",
						DevblocksPlatform::strEscapeHtml($keys[$field_key]['label'] ?? $field_key)
					);
					return false;
				}
			}
		}
		
		// Use the context to validate sync options, if available
		if(method_exists($this->_context_ext, 'importValidateSync')) {
			if(true !== ($error = $this->_context_ext->importValidateSync($mapping->getSyncColumns())))
				return false;
		}
		
		return true;
	}
	
	public function getFileRecordByOffset(int $offset, int $length, Mapping $mapping) : array {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$context_keys = $this->getRecordKeys();
		$parts = [];
		
		// Seek to the start of the record
		fseek($this->_fp, $offset);
		
		// JSONL or CSV
		if($this->getImportFormat() == 'jsonl') {
			if(false !== ($line = fread($this->_fp, $length))) {
				if(false !== ($parts = json_decode($line, true)) && is_array($parts))
					$parts = array_values($parts);
			}
			
		} else { // CSV
			$parts = fgetcsv($this->_fp, 128_000, ',', '"');
		}
		
		if(empty($parts) || (1==count($parts) && is_null($parts[0])))
			return [];
		
		// Dictionary for placeholders
		
		$dict = new \DevblocksDictionaryDelegate([]);
		
		foreach($parts as $idx => $part) {
			$dict->set('column_' . ($idx + 1), $part); // 0-based to 1-based
		}
		
		// Field values
		
		$field = $mapping->getFields();
		$column = $mapping->getColumns();
		$column_custom = $mapping->getCustomColumns();
		
		$field_values = array_fill_keys(array_values($field), null);
		
		foreach($field as $idx => $key) {
			if (!isset($context_keys[$key]))
				continue;
			
			$col = $column[$idx] ?? null;
			
			// Are we providing custom values?
			if ($col == 'custom') {
				$val = $tpl_builder->build($column_custom[$idx], $dict);
				
			// Are we referencing a column number from the CSV file?
			} elseif (is_numeric($col)) {
				$val = $parts[$col];
				
				// Otherwise, use a literal value.
			} else {
				$val = $col;
			}
			
			// Must be a non-empty string
			if (!is_string($val) || 0 == strlen($val))
				$val = null;
			
			$field_values[$key] = $val;
		}
		
		// Meta
		return [
			'line' => $parts,
			'object_id' => null,
			'values' => $field_values,
		];
	}
	
	public function bulkFormatRecordFields(array $results, Mapping $mapping) : array {
		$field = $mapping->getFields();
		$context_keys = $this->getRecordKeys();
		
		if(!($this->_context_ext instanceof IDevblocksContextImport))
			return [];
		
		// Load each field value across a set of dictionaries at once (batch lookup)
		foreach($field as $key) {
			// What type of field is this?
			$type = $context_keys[$key]['type'];
			//$value = null;
			
			// Can we automatically format the value?
			
			$distinct_values = array_unique(array_map(fn($result) => $result['values'][$key] ?? null, $results));
			
			switch($type) {
				// Efficiently bulk look up addresses
				case 'ctx_' . CerberusContexts::CONTEXT_ADDRESS:
					$addresses = array_column(
						DAO_Address::lookupAddresses(array_filter($distinct_values), true),
						'id',
						'email'
					);
					
					foreach($results as &$result) {
						$result['values'][$key] = $addresses[$result['values'][$key]] ?? null;
					}
					break;
				
				// Efficiency bulk lookup orgs
				case 'ctx_' . CerberusContexts::CONTEXT_ORG:
					$orgs = array_column(
						array_map(fn($v) => DAO_ContactOrg::lookup($v, true), array_filter($distinct_values)),
						'id',
						'name'
					);
					
					foreach($results as &$result) {
						$result['values'][$key] = $orgs[$result['values'][$key]] ?? null;
					}
					break;
				
				case Model_CustomField::TYPE_CHECKBOX:
					foreach($results as &$result) {
						if(null === ($val = $result['values'][$key] ?? null))
							continue;
						
						$result['values'][$key] = DevblocksPlatform::services()->string()->toBool($val) ? 1 : 0;
					}
					break;
				
				case Model_CustomField::TYPE_DATE:
					foreach($results as &$result) {
						if(null === ($val = $result['values'][$key] ?? null))
							continue;
						
						if($val && !is_numeric($val))
							$result['values'][$key] = strtotime($val);
					}
					break;

				case Model_CustomField::TYPE_LIST:
					foreach($results as &$result) {
						$val = $result['values'][$key] ?? '';
						$result['values'][$key] = DevblocksPlatform::parseCsvString($val);
					}
					break;
				
				case Model_CustomField::TYPE_MULTI_CHECKBOX:
					foreach($results as &$result) {
						$val = $result['values'][$key] ?? '';
						$result['values'][$key] = DevblocksPlatform::parseCsvString(str_replace(
							'\"',
							'',
							$val
						));
					}
					break;
				
				case Model_CustomField::TYPE_NUMBER:
					foreach($results as &$result) {
						if(null === ($val = $result['values'][$key] ?? null))
							continue;
						
						$result['values'][$key] = intval($val);
					}
					break;
				
				case Model_CustomField::TYPE_WORKER:
				case 'ctx_' . CerberusContexts::CONTEXT_WORKER:
					$workers = DAO_Worker::getAllActive();
					
					foreach($results as &$result) {
						if(null === ($val = $result['values'][$key] ?? null))
							continue;
						
						$val_worker_id = 0;
						
						if(0 == strcasecmp(strval($val), 'me')) {
							$val_worker_id = $active_worker->id ?? 0;
						}
						
						foreach($workers as $worker_id => $worker) {
							if(!empty($val_worker_id))
								break;
							
							$worker_name = $worker->getName();
							
							if(false !== stristr($worker_name, $val)) {
								$val_worker_id = $worker_id;
							}
						}
						
						$result['values'][$key] = $val_worker_id;
					}
					break;

//				case Model_CustomField::TYPE_DROPDOWN:
//				case Model_CustomField::TYPE_MULTI_LINE:
//				case Model_CustomField::TYPE_SINGLE_LINE:
//				case Model_CustomField::TYPE_URL:
//				default:
//					$value = $val;
//					break;
			}
			
			foreach ($results as &$result) {
				$result['values'][$key] = $this->_context_ext->importKeyValue($key, $result['values'][$key] ?? null);
			}
		}
		
		return $results;
	}
	
	public function bulkTagUpserts(array $results, Mapping $mapping) : array {
		$sync_dupes = $mapping->getSyncColumns();
		$context_keys = $this->getRecordKeys();
		
		// If we're not syncing, abort
		if(!$sync_dupes)
			return $results;
		
		$sync_fields = [];
		$sync_dupes_search_keys = [];
		$has_custom_fields = false;
		
		// If we're forced to match this key, or it's selected for upserts
		foreach($context_keys as $key => $context_key) {
			if (($context_key['force_match'] ?? null) || in_array($key, $sync_dupes)) {
				$vals = array_filter(array_unique(array_map(fn($result) => $result['values'][$key] ?? null, $results)));
				if($vals) {
					$sync_fields[$key] = new DevblocksSearchCriteria($context_key['param'], DevblocksSearchCriteria::OPER_IN, $vals);
					$sync_dupes_search_keys[] = $context_key['param'];
					if(str_starts_with($key, 'cf_')) $has_custom_fields = true;
				}
			}
		}
		
		// In a single query, fetch all rows that match a value in all sync columns (we join in app)
		$view = $this->_context_ext->getTempView();
		$view->view_columns = [];
		$view->addParams(array_values($sync_fields), true);
		$view->renderLimit = 250;
		$view->renderTotal = false;
		list($data) = $view->getData();
		
		// Lazy add custom fields to the page (if we have custom fields)
		if($has_custom_fields) {
			$field_values = array_map(
				function ($values) {
					return array_combine(
						array_map(fn($key) => 'cf_' . $key, array_keys($values)),
						$values
					);
				},
				\DAO_CustomFieldValue::getValuesByContextIds($this->_context_ext->id, array_keys($data))
			);
			
			$data =
				array_combine(
					array_keys($data),
					array_map(
						fn($row_id) => array_merge($data[$row_id], $field_values[$row_id] ?? []),
						array_keys($data)
					)
				)
			;
		}
		
		// Hash all the results on the sync columns
		$corpus_hashes =
			array_combine(
				array_map(
					fn($row) => sha1(implode("\x00", array_intersect_key($row, array_flip($sync_dupes_search_keys)))),
					$data
				),
				array_keys($data)
			)
		;
		
		// If we have hash collisions in our records, tag the target object_id for upserts
		foreach($results as &$result) {
			$hash = sha1(implode("\x00", array_intersect_key($result['values'], $sync_fields)));
			
			if(array_key_exists($hash, $corpus_hashes))
				$result['object_id'] = $corpus_hashes[$hash];
		}
		
		return $results;
	}
	
	public function importRecords(array $records, Mapping $mapping) : bool {
		if(!($this->_context_ext instanceof IDevblocksContextImport))
			return false;
		
		foreach($records as $record) {
			$fields = [];
			$custom_fields = [];
			
			$meta = [];
			$meta['line'] = $record['line'] ?? [];
			$meta['fields'] = $mapping->getFields();
			$meta['columns'] = $mapping->getColumns();
			$meta['virtual_fields'] = [];
			$meta['object_id'] = $record['object_id'] ?? null;
			
			foreach($this->getRecordKeys() as $key => $context_key) {
				$value = $record['values'][$key] ?? null;
				
				if (is_null($value))
					continue;
				
				// Are we setting a custom field?
				$cf_id = null;
				if (str_starts_with($key, 'cf_')) {
					$cf_id = substr($key, 3);
				}
				
				// Is this a virtual field?
				if (str_starts_with($key, '_')) {
					$meta['virtual_fields'][$key] = $value;
					
					// ...or is it a normal DAO field?
				} else {
					if (is_null($cf_id)) {
						$fields[$key] = $value;
					} else {
						$custom_fields[$cf_id] = $value;
					}
				}
			}
			
			$this->_context_ext->importSaveObject($fields, $custom_fields, $meta);
		}
		
		return true;
	}
}