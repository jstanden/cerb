<?php
namespace Cerb\Records;

use CerberusContexts;
use DAO_Worker;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Extension_DevblocksContext;
use Model_CustomField;

class WorklistExporter {
	private Extension_DevblocksContext $_context_ext;
	private array $_global_labels = [];
	private array $_global_types = [];

	public function __construct(string $context) {
		if(!($context_ext = Extension_DevblocksContext::get($context)))
			throw new \RuntimeException(sprintf('Invalid export context: %s', $context));

		$this->_context_ext = $context_ext;

		$global_labels = $global_values = [];
		CerberusContexts::getContext($context_ext->id, null, $global_labels, $global_values, null, true);

		$this->_global_labels = $global_labels;
		$this->_global_types = $global_values['_types'] ?? [];
	}

	public function getRecordExtension() : Extension_DevblocksContext {
		return $this->_context_ext;
	}

	/**
	 * Render the rows for a single chunk. No headers, no JSON wrapper, no XML root.
	 *
	 * @param int[] $record_ids
	 * @param array $metadata The queue_job metadata
	 */
	public function renderChunkBytes(array $record_ids, array $metadata) : string {
		if(!$record_ids) return '';

		$dicts = $this->_loadDictionariesForIds($record_ids, $metadata);

		if(!$dicts)
			return '';

		$export_as = $metadata['export_as'] ?? 'csv';
		$export_mode = $metadata['export_mode'] ?? '';
		$tokens = $metadata['tokens'] ?? [];
		$format_timestamps = !empty($metadata['format_timestamps']);

		if('kata' == $export_mode) {
			DevblocksDictionaryDelegate::bulkLazyLoad($dicts, 'customfields');
			$columns = $this->parseExportColumnsKata($metadata['export_kata'] ?? '');
		} else {
			foreach($tokens as $token)
				DevblocksDictionaryDelegate::bulkLazyLoad($dicts, $token);
			$columns = null;
		}

		return match($export_as) {
			'csv'   => $this->_renderCsvChunk($dicts, $columns, $tokens, $format_timestamps),
			'json'  => $this->_renderJsonChunk($dicts, $columns, $tokens, $format_timestamps),
			'jsonl' => $this->_renderJsonlChunk($dicts, $columns, $tokens, $format_timestamps),
			'xml'   => $this->_renderXmlChunk($dicts, $columns, $tokens, $format_timestamps),
			default => '',
		};
	}

	public function renderPrologue(array $metadata) : string {
		$export_as = $metadata['export_as'] ?? 'csv';
		$export_mode = $metadata['export_mode'] ?? '';
		$tokens = $metadata['tokens'] ?? [];

		switch($export_as) {
			case 'csv':
				if('kata' == $export_mode) {
					$labels = [];
					foreach($this->parseExportColumnsKata($metadata['export_kata'] ?? '') as $column_name => $column) {
						$labels[] = $column['label'] ?? DevblocksPlatform::strTitleCase($column_name);
					}
				} else {
					$labels = array_map(fn($t) => trim($this->_global_labels[$t] ?? $t), $tokens);
				}
				$fp = fopen('php://memory', 'r+');
				fputcsv($fp, $labels);
				rewind($fp);
				$out = stream_get_contents($fp);
				fclose($fp);
				return $out;

			case 'json':
				if('kata' == $export_mode)
					return "{\"results\": [\n";

				$fields = [];
				foreach($tokens as $token) {
					$fields[$token] = [
						'label' => $this->_global_labels[$token] ?? null,
						'type'  => $this->_global_types[$token] ?? null,
					];
				}
				return "{\n\"fields\":" . json_encode($fields) . ",\n\"results\": [\n";

			case 'jsonl':
				return '';

			case 'xml':
				$out = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<export>\n";

				if('kata' != $export_mode) {
					$xml_fields = simplexml_load_string("<fields/>");
					foreach($tokens as $token) {
						$field = $xml_fields->addChild("field");
						$field->addAttribute('key', $token);
						$field->addChild('label', $this->_global_labels[$token] ?? '');
						$field->addChild('type', $this->_global_types[$token] ?? '');
					}
					$dom = dom_import_simplexml($xml_fields);
					$out .= $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement) . "\n";
				}

				$out .= "<results>\n";
				return $out;
		}

		return '';
	}

	public function renderEpilogue(array $metadata) : string {
		return match($metadata['export_as'] ?? 'csv') {
			'json'  => "\n]\n}",
			'xml'   => "\n</results>\n</export>\n",
			default => '',
		};
	}

	/**
	 * Separator inserted between chunks. CSV/JSONL/XML chunks self-delimit; JSON needs a comma.
	 */
	public function getChunkSeparator(array $metadata) : string {
		return ('json' == ($metadata['export_as'] ?? 'csv')) ? ',' : '';
	}

	public function parseExportColumnsKata(string $export_kata) : array {
		$kata = DevblocksPlatform::services()->kata();
		$error = null;

		if(false === ($parsed = $kata->parse($export_kata, $error)))
			return [];

		if(false === ($parsed = $kata->formatTree($parsed, null, $error)))
			return [];

		if(!is_array($parsed))
			return [];

		$columns = [];

		foreach($parsed as $column_key => $column_data) {
			[$column_type, $column_name] = array_pad(explode('/', $column_key, 2), 2, null);

			if('column' != $column_type || !$column_name)
				continue;

			$annotations = '';
			foreach($column_data as $k => $v) {
				[$k, $k_annotations] = array_pad(explode('@', $k, 2), 2, null);
				if('value' == $k) {
					$column_data['value'] = $v;
					$annotations = $k_annotations ?? '';
				}
			}

			$columns[$column_name] = [
				'label'       => $column_data['label'] ?? DevblocksPlatform::strTitleCase($column_name),
				'value'       => $column_data['value'] ?? sprintf('{{%s}}', $column_name),
				'annotations' => $annotations,
			];
		}

		return $columns;
	}

	private function _loadDictionariesForIds(array $record_ids, array $metadata) : array {
		$models = CerberusContexts::getModels($this->_context_ext->id, $record_ids);

		if(!$models)
			return [];

		// Filter by the job-owning worker's ACL
		$worker_id = intval($metadata['worker_id'] ?? 0);

		if($worker_id && ($worker = DAO_Worker::get($worker_id))) {
			$models = CerberusContexts::filterModelsByActorReadable(
				get_class($this->_context_ext), $models, $worker
			);
		}

		if(!$models)
			return [];

		$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $this->_context_ext->id);

		foreach($dicts as $dict)
			$dict->scrubKeys('_types');

		// Preserve the input ID ordering inside the chunk
		$ordered = [];
		foreach($record_ids as $id) {
			if(isset($dicts[$id]))
				$ordered[$id] = $dicts[$id];
		}

		return $ordered;
	}

	private function _renderCsvChunk(array $dicts, ?array $columns, array $tokens, bool $format_timestamps) : string {
		$fp = fopen('php://memory', 'r+');

		if($columns) {
			foreach($dicts as $dict) {
				$fields = [];
				foreach($columns as $column_name => $column) {
					$value = $this->_renderKataColumnValue($column_name, $column, $dict);
					$fields[] = is_scalar($value) ? $value : json_encode($value);
				}
				fputcsv($fp, $fields);
			}
		} else {
			foreach($dicts as $dict) {
				$fields = [];
				foreach($tokens as $token) {
					$value = $this->_extractTokenValue($dict, $token, $format_timestamps);

					if(is_array($value))
						$value = json_encode($value);

					if(!is_string($value) && !is_numeric($value))
						$value = '';

					$fields[] = $value;
				}
				fputcsv($fp, $fields);
			}
		}

		rewind($fp);
		$out = stream_get_contents($fp);
		fclose($fp);

		return $out;
	}

	private function _renderJsonChunk(array $dicts, ?array $columns, array $tokens, bool $format_timestamps) : string {
		$objects = [];

		if($columns) {
			foreach($dicts as $dict) {
				$object = [];
				foreach($columns as $column_name => $column) {
					$object[$column_name] = $this->_renderKataColumnValue($column_name, $column, $dict);
				}
				$objects[] = $object;
			}
		} else {
			foreach($dicts as $dict) {
				$object = [];
				foreach($tokens as $token) {
					$object[$token] = $this->_extractTokenValue($dict, $token, $format_timestamps);
				}
				$objects[] = $object;
			}
		}

		// Strip the outer `[` and `]` so the completion handler can join chunks with commas
		return trim(json_encode($objects), '[]');
	}

	private function _renderJsonlChunk(array $dicts, ?array $columns, array $tokens, bool $format_timestamps) : string {
		$out = '';

		if($columns) {
			foreach($dicts as $dict) {
				$object = [];
				foreach($columns as $column_name => $column) {
					$object[$column_name] = $this->_renderKataColumnValue($column_name, $column, $dict);
				}
				$out .= json_encode($object) . "\n";
			}
		} else {
			foreach($dicts as $dict) {
				$object = [];
				foreach($tokens as $token) {
					$object[$token] = $this->_extractTokenValue($dict, $token, $format_timestamps);
				}
				$out .= json_encode($object) . "\n";
			}
		}

		return $out;
	}

	private function _renderXmlChunk(array $dicts, ?array $columns, array $tokens, bool $format_timestamps) : string {
		$out = '';

		foreach($dicts as $dict) {
			$xml_result = simplexml_load_string("<result/>");

			if($columns) {
				foreach($columns as $column_name => $column) {
					$value = $this->_renderKataColumnValue($column_name, $column, $dict);
					$field = $xml_result->addChild("field", DevblocksPlatform::strEscapeHtml(is_scalar($value) ? $value : json_encode($value)));
					$field->addAttribute("key", $column_name);
				}
			} else {
				foreach($tokens as $token) {
					$value = $this->_extractTokenValue($dict, $token, $format_timestamps);

					if(is_array($value))
						$value = json_encode($value);

					if(!is_string($value) && !is_numeric($value))
						$value = '';

					$field = $xml_result->addChild("field", DevblocksPlatform::strEscapeHtml($value));
					$field->addAttribute("key", $token);
				}
			}

			$dom = dom_import_simplexml($xml_result);
			$out .= $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);
		}

		return $out;
	}

	private function _extractTokenValue(DevblocksDictionaryDelegate $dict, string $token, bool $format_timestamps) : mixed {
		$value = $dict->exists($token) ? $dict->get($token) : '';

		if(($this->_global_types[$token] ?? null) == Model_CustomField::TYPE_DATE && $format_timestamps) {
			if(empty($value)) {
				$value = '';
			} else if(is_numeric($value)) {
				$value = date('r', $value);
			}
		}

		return $value;
	}

	private function _renderKataColumnValue(string $column_name, array $column, DevblocksDictionaryDelegate $dict) : mixed {
		$kata = DevblocksPlatform::services()->kata();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		$column_value = $column['value'] ?? '';

		if($column['annotations'] ?? false) {
			return $kata->formatTree(
				['value@' . $column['annotations'] => $column_value],
				$dict
			)['value'] ?? '';
		}

		if(is_array($column_value))
			return $kata->formatTree($column_value, $dict);

		return $tpl_builder->build($column_value, $dict);
	}
}
