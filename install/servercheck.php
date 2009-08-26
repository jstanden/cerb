<html>
<head>
	<title>Cerberus Helpdesk 4.x - Server Environment Checker</title>
	
	<style>
	BODY {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}
	.pass {color:green;font-weight:bold;}
	.fail {color:red;font-weight:bold;}
	</style>
</head>

<body>
<h1>Cerberus Helpdesk 4.x - Server Environment Checker</h1>

<?php
$results = array();
$fails = 0;

// PHP Version
if(version_compare(PHP_VERSION,"5.2") >=0) {
	$results['php_version'] = PHP_VERSION;
} else {
	$results['php_version'] = false;
	$fails++;
}

// File Uploads
$ini_file_uploads = ini_get("file_uploads");
if($ini_file_uploads == 1 || strcasecmp($ini_file_uploads,"on")==0) {
	$results['file_uploads'] = true;
} else {
	$results['file_uploads'] = false;
	$fails++;
}

// File Upload Temporary Directory
$ini_upload_tmp_dir = ini_get("upload_tmp_dir");
if(!empty($ini_upload_tmp_dir)) {
	$results['upload_tmp_dir'] = true;
} else {
	$results['upload_tmp_dir'] = false;
	//$fails++; // Not fatal
}

// Memory Limit
$memory_limit = ini_get("memory_limit");
if ($memory_limit == '') { // empty string means failure or not defined, assume no compiled memory limits
	$results['memory_limit'] = true;
} else {
	$ini_memory_limit = intval($memory_limit);
	if($ini_memory_limit >= 16) {
		$results['memory_limit'] = true;
	} else {
		$results['memory_limit'] = false;
		$fails++;
	}
}

// Extension: MySQL
if(extension_loaded("mysql")) {
	$results['ext_mysql'] = true;
} else {
	$results['ext_mysql'] = false;
	$fails++;
}

// Extension: Sessions
if(extension_loaded("session")) {
	$results['ext_session'] = true;
} else {
	$results['ext_session'] = false;
	$fails++;
}

// Extension: PCRE
if(extension_loaded("pcre")) {
	$results['ext_pcre'] = true;
} else {
	$results['ext_pcre'] = false;
	$fails++;
}

// Extension: GD
if(extension_loaded("gd") && function_exists('imagettfbbox')) {
	$results['ext_gd'] = true;
} else {
	$results['ext_gd'] = false;
	$fails++;
}

// Extension: IMAP
if(extension_loaded("imap")) {
	$results['ext_imap'] = true;
} else {
	$results['ext_imap'] = false;
	$fails++;
}

// Extension: MailParse
if(extension_loaded("mailparse")) {
	$results['ext_mailparse'] = true;
} else {
	$results['ext_mailparse'] = false;
	$fails++;
}

// Extension: mbstring
if(extension_loaded("mbstring")) {
	$results['ext_mbstring'] = true;
} else {
	$results['ext_mbstring'] = false;
	$fails++;
}

// Extension: XML
if(extension_loaded("xml")) {
	$results['ext_xml'] = true;
} else {
	$results['ext_xml'] = false;
	$fails++;
}

// Extension: SimpleXML
if(extension_loaded("simplexml")) {
	$results['ext_simplexml'] = true;
} else {
	$results['ext_simplexml'] = false;
	$fails++;
}

// Extension: DOM
if(extension_loaded("dom")) {
	$results['ext_dom'] = true;
} else {
	$results['ext_dom'] = false;
	$fails++;
}

// Extension: SPL
if(extension_loaded("spl")) {
	$results['ext_spl'] = true;
} else {
	$results['ext_spl'] = false;
	$fails++;
}

if($fails) {
	echo "The following problems prevent you from running Cerberus Helpdesk 4.x:<span class='fail'><ul>";
	
	if(!$results['php_version'])
		echo '<li>Cerberus Helpdesk 4.x requires PHP 5.2 or later. Your server PHP version is '.PHP_VERSION.'.</li>';

	if(!$results['file_uploads'])
		echo '<li>file_uploads is disabled in your php.ini file. Please enable it.</li>';
		
	if(!$results['upload_tmp_dir'])
		echo '<li>upload_tmp_dir is empty in your php.ini file. Please set it.</li>';
		
	if(!$results['memory_limit'])
		echo '<li>memory_limit must be 16M or larger (32M recommended) in your php.ini file.  Please increase it.</li>';
		
	if(!$results['ext_mysql'])
		echo "<li>The 'MySQL' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_session'])
		echo "<li>The 'Session' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_pcre'])
		echo "<li>The 'PCRE' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_spl'])
		echo "<li>The 'SPL' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_gd'])
		echo "<li>The 'GD' PHP extension (with FreeType library support) is required.  Please enable them.</li>";
		
	if(!$results['ext_imap'])
		echo "<li>The 'IMAP' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_mailparse'])
		echo "<li>The 'MailParse' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_mbstring'])
		echo "<li>The 'MbString' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_xml'])
		echo "<li>The 'XML' PHP extension is required.  Please enable it.</li>";
		
	if(!$results['ext_dom'])
		echo "<li>The 'DOM' PHP extension is required.  Please enable it.</li>";
		
		if(!$results['ext_simplexml'])
		echo "<li>The 'SimpleXML' PHP extension is required.  Please enable it.</li>";
		
	echo "</ul></span><br>Please correct these issues and try again.<br>";
	
} else {
	echo "<span class='pass'>Your server is Cerberus Helpdesk 4.x compatible!</span><br>";
	
}

?>
</body>

</html>
