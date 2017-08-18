<?php
require(getcwd() . '/../../../framework.config.php');
require(DEVBLOCKS_PATH . 'Devblocks.class.php');

$db = DevblocksPlatform::services()->database();

$table_name = DevblocksPlatform::importGPC($_REQUEST['table'], 'string', '');

if(empty($table_name))
	die("The 'table' parameter is required.");

$sql = sprintf("select column_name, column_type, data_type, numeric_precision, character_maximum_length, numeric_scale from information_schema.columns where table_schema = %s and table_name = %s order by column_name",
	$db->qstr(APP_DB_DATABASE),
	$db->qstr($table_name)
);
$results = $db->GetArrayMaster($sql);

echo "<textarea rows='40' cols='100'>";

$out = <<< EOF

	private function __construct() {}

	static function getFields() {
		\$validation = DevblocksPlatform::services()->validation();
		
%s
		return \$validation->getFields();
	}
EOF;

$fields = '';

if(is_array($results))
foreach($results as $result) {
	$type = null;
	
	if(in_array($result['data_type'],['int','bigint']) && DevblocksPlatform::strEndsWith($result['column_name'], '_id')) {
		$type = sprintf("->id()");
		
	} else if(DevblocksPlatform::strStartsWith($result['column_name'], ['owner_context']) || $result['column_name'] == 'context') {
		$type = sprintf("->context()");
		
	} else if(DevblocksPlatform::strStartsWith($result['column_name'], ['updated','created']) || DevblocksPlatform::strEndsWith($result['column_name'], ['_at','_date'])) {
		$type = sprintf("->timestamp()");
		
	} else {
		switch($result['data_type']) {
			case 'bigint':
				switch($result['column_name']) {
					default:
						$type = sprintf("->uint(8)");
						break;
				}
				break;
			case 'date':
				switch($result['column_name']) {
					default:
						$type = sprintf("->string()");
						break;
				}
				break;
			case 'decimal':
				switch($result['column_name']) {
					default:
						$type = sprintf("->float()");
						break;
				}
				break;
			case 'int':
				switch($result['column_name']) {
					case 'id':
						$type = sprintf("->id()\n\t\t\t->setEditable(false)");
						break;
					default:
						$type = sprintf("->uint(4)");
						break;
				}
				break;
			case 'mediumint':
				switch($result['column_name']) {
					default:
						$type = sprintf("->uint(3)");
						break;
				}
				break;
			case 'smallint':
				switch($result['column_name']) {
					default:
						$type = sprintf("->uint(2)");
						break;
				}
				break;
			case 'tinyint':
				if(DevblocksPlatform::strStartsWith($result['column_name'], ['is_','has_'])) {
					$type = sprintf("->bit()");
					break;
				}
				
				switch($result['column_name']) {
					default:
						$type = sprintf("->uint(1)");
						break;
				}
				break;
			case 'tinytext':
			case 'mediumtext':
			case 'text':
			case 'longtext':
				switch($result['column_name']) {
					default:
						$type = sprintf("->string()\n\t\t\t->setMaxLength(%d)",
							$result['character_maximum_length']
						);
						break;
				}
				break;
			case 'char':
			case 'varchar':
				switch($result['column_name']) {
					default:
						$type = sprintf("->string()\n\t\t\t->setMaxLength(%d)",
							$result['character_maximum_length']
						);
						break;
				}
				break;
		}
	}
	
	$fields .= sprintf("\t\t// %s\n\t\t\$validation\n\t\t\t->addField(self::%s)\n\t\t\t%s\n\t\t\t;\n",
		$result['column_type'],
		DevblocksPlatform::strUpper($result['column_name']),
		$type
	);
}

echo sprintf($out, $fields);

echo "</textarea>";

var_dump($results);