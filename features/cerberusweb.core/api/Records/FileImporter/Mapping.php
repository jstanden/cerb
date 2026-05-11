<?php
namespace Cerb\Records\FileImporter;

class Mapping implements \JsonSerializable {
	private array $_columns;
	private array $_custom_columns;
	private array $_fields;
	private array $_sync_columns;
	
	public function __construct(array $fields, array $columns, array $custom_columns, array $sync_columns) {
		$this->_columns = $columns;
		$this->_custom_columns = $custom_columns;
		$this->_fields = $fields;
		$this->_sync_columns = $sync_columns;
	}
	
	public function jsonSerialize() : array {
		return [
			'columns' => $this->_columns,
			'custom_columns' => $this->_custom_columns,
			'fields' => $this->_fields,
			'sync_columns' => $this->_sync_columns,
		];
	}
	
	public function getColumns() : array {
		return $this->_columns;
	}
	
	public function getCustomColumns() : array {
		return $this->_custom_columns;
	}
	
	public function getFields() : array {
		return $this->_fields;
	}
	
	public function getSyncColumns() : array {
		return $this->_sync_columns;
	}
}