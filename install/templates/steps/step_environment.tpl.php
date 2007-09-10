<h2>Checking Server Environment</h2>

<form action="index.php" method="POST">
<b>PHP Version... </b> 
{if !$results.php_version}
	<span class="bad">{$translate->_('installer.failed')}!  PHP 5.0.0 or later is required.</span>
{else}
	<span class="good">{$translate->_('installer.passed')}! (PHP {$results.php_version})</span>
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

<b>PHP Extension (PCRE)... </b> 
{if !$results.ext_pcre}
	<span class="bad">Error! PHP must have the 'PCRE' extension enabled.</span>
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

<b>PHP Extension (SimpleXML)... </b> 
{if !$results.ext_simplexml}
	<span class="bad">Error! PHP must have the 'SimpleXML' extension enabled.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP PEAR (Mail)... </b> 
{if !$results.pear_mail}
	<span class="bad">Error! PHP must have the 'Mail' PEAR package installed.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP PEAR (Mail_Mime)... </b> 
{if !$results.pear_mail_mime}
	<span class="bad">Error! PHP must have the 'Mail_Mime' PEAR package installed.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP PEAR (Mail_mimeDecode)... </b> 
{if !$results.pear_mail_mimedecode}
	<span class="bad">Error! PHP must have the 'Mail_mimeDecode' PEAR package installed.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP PEAR (Mail_RFC822)... </b> 
{if !$results.pear_mail_rfc822}
	<span class="bad">Error! PHP must have the 'Mail_RFC822' PEAR package installed.</span>
{else}
	<span class="good">Passed!</span>
{/if}
<br>
<br>

<b>PHP PEAR (Text_Password)... </b> 
{if !$results.pear_text_password}
	<span class="bad">Error! PHP must have the 'Text_Password' PEAR package installed.</span>
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

{if !$fails}
	<input type="hidden" name="step" value="{$smarty.const.STEP_LICENSE}">
	<input type="submit" value="Next Step &gt;&gt;">
{else}
	<input type="hidden" name="step" value="{$smarty.const.STEP_ENVIRONMENT}">
	<input type="submit" value="Try Again">
{/if}
</form>