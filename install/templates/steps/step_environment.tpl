<h2>Checking Server Environment</h2>

<form action="index.php" method="POST">
<b>PHP Version... </b> 
{if !$results.php_version}
	<span class="bad">Failed!  PHP 5.2 or later is required.</span>
{else}
	<span class="good">Passed! (PHP {$results.php_version})</span>
{/if}
<br>
<br>

<b>PHP Extension (Session)... </b> 
{if !$results.ext_session}
	<span class="bad">Error! PHP must have the 'Sessions' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (cURL)... </b> 
{if !$results.ext_curl}
	<span class="bad">Error! PHP must have the 'cURL' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (PCRE)... </b> 
{if !$results.ext_pcre}
	<span class="bad">Error! PHP must have the 'PCRE' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (SPL)... </b> 
{if !$results.ext_spl}
	<span class="bad">Error! PHP must have the 'SPL' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (ctype)... </b> 
{if !$results.ext_ctype}
	<span class="bad">Error! PHP must have the 'ctype' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (GD)... </b> 
{if !$results.ext_gd}
	<span class="bad">Error! PHP must have the 'GD' extension enabled (with FreeType library support).</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (IMAP)... </b> 
{if !$results.ext_imap}
	<span class="bad">Error! PHP must have the 'IMAP' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (MailParse)... </b> 
{if !$results.ext_mailparse}
	<span class="bad">Error! PHP must have the 'MailParse' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (mbstring)... </b> 
{if !$results.ext_mbstring}
	<span class="bad">Error! PHP must have the 'mbstring' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (DOM)... </b> 
{if !$results.ext_dom}
	<span class="bad">Error! PHP must have the 'DOM' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (XML)... </b> 
{if !$results.ext_xml}
	<span class="bad">Error! PHP must have the 'XML' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (SimpleXML)... </b> 
{if !$results.ext_simplexml}
	<span class="bad">Error! PHP must have the 'SimpleXML' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP Extension (JSON)... </b> 
{if !$results.ext_json}
	<span class="bad">Error! PHP must have the 'JSON' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP.INI File_Uploads... </b> 
{if !$results.file_uploads}
	<span class="bad">Failure!  file_uploads must be enabled in your php.ini file.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP.INI Upload_Tmp_Dir... </b> 
{if !$results.upload_tmp_dir}
	<span class="warning">Warning! upload_tmp_dir should be set in your php.ini file.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP.INI Memory_Limit... </b> 
{if !$results.memory_limit}
	<span class="bad">Failure! memory_limit should be 16M or higher (32M recommended) in your php.ini file.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

{if !$fails}
	<input type="hidden" name="step" value="{$smarty.const.STEP_LICENSE}">
	<input type="submit" value="Next Step &gt;&gt;">
{else}
	<input type="hidden" name="step" value="{$smarty.const.STEP_ENVIRONMENT}">
	<input type="submit" value="Try Again">
{/if}
</form>