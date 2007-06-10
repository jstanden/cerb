<?php
abstract class CerberusImporterExtension extends DevblocksExtension {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function configure() {}
	function saveConfiguration() {}
	function import($start=1, $limit=100) {}
};

?>