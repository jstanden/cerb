<?php
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

define('ES_INDEX', '');

define('OUTPUT_SIZE', 10000000);
define('MSG_MAX_SIZE', 5000);

$length = 0;
$buffer = '';
$count = 0;

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if(!$db)
	die("Failed to connect to DB.");

// [TODO] Make sure we're running in CLI
// [TODO] Handle other types (address, org, etc) as CLI args

// [TODO] This still includes the first quoted line of content from the concat_ws()
$sql = sprintf("SELECT m.id, m.created_date, concat_ws(' ', a.email, c.first_name, c.last_name, o.name, t.mask, t.subject, substring(mc.data, 1, greatest(locate('\n', mc.data, %d), %d))) AS content ".
	"FROM message m ".
	"INNER JOIN ticket t ON (t.id=m.ticket_id) ".
	"INNER JOIN address a ON (a.id=m.address_id) ".
	"LEFT JOIN contact c ON (c.id=a.contact_id) ".
	"LEFT JOIN contact_org o ON (o.id=c.org_id) ".
	"INNER JOIN storage_message_content mc ON (mc.id=m.id and mc.chunk=1) ",
	MSG_MAX_SIZE,
	MSG_MAX_SIZE
);

$stmt = $db->prepare($sql);
$stmt->attr_set(MYSQLI_STMT_ATTR_CURSOR_TYPE, MYSQLI_CURSOR_TYPE_READ_ONLY);
$stmt->attr_set(MYSQLI_STMT_ATTR_PREFETCH_ROWS, 100);

if($stmt->execute()) {
	$stmt->bind_result($id, $created, $content);
	
	while($stmt->fetch()) {
		// Strip reply quotes
		$content = preg_replace("/(^\>(.*)\$)/m", "", $content);
		
		$json_meta = json_encode(array('index' => array('_index' => ES_INDEX, '_type' => 'message_content', '_id' => $id))) . "\n";
		$json_data = json_encode(array('created' => $created, 'content' => $content)) . "\n";
		
		if($length && $length + strlen($json_meta . $json_data) > OUTPUT_SIZE) {
			$buffer .= "\n";
			$fp = fopen(sprintf('dump_%06d.json', $count++), "w");
			fputs($fp, $buffer);
			fclose($fp);
			$buffer = '';
			$length = 0;
		}
		
		$buffer .= $json_meta . $json_data;
		$length = strlen($buffer);
	};
	
	if(!empty($buffer)) {
		$buffer .= "\n";
		$fp = fopen(sprintf('dump_%06d.json', $count++), "w");
		fputs($fp, $buffer);
		fclose($fp);
		$buffer = '';
		$length = 0;
	}
}

$stmt->close();
$db->close();