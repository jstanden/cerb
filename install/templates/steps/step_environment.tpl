<h2>Server Environment</h2>

{* Calculate totals for summary *}
{$total_checks = 20}
{$passed_checks = 0}
{if $results.php_version}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_session}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_curl}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_pcre}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_spl}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_ctype}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_gd}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_mailparse && $results.mailparse_version}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_mbstring}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_mysqli}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_mysqlnd}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_dom}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_xml}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_simplexml}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_json}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_openssl}{$passed_checks = $passed_checks + 1}{/if}
{if $results.ext_yaml}{$passed_checks = $passed_checks + 1}{/if}
{if $results.file_uploads}{$passed_checks = $passed_checks + 1}{/if}
{if $results.memory_limit}{$passed_checks = $passed_checks + 1}{/if}
{if $results.upload_tmp_dir}{$passed_checks = $passed_checks + 1}{/if}

{* Summary box *}
<div class="check-summary {if $fails == 0}success{else}error{/if}">
	<span class="icon">
		{if $fails == 0}
			<span class="icon-success">{call name="icon" icon="circle-check" size=24}</span>
		{else}
			<span class="icon-error">{call name="icon" icon="circle-x" size=24}</span>
		{/if}
	</span>
	<span class="text">
		{if $fails == 0}
			All {$passed_checks} requirements passed
		{else}
			{$passed_checks} of {$total_checks} requirements passed ({$fails} failed)
		{/if}
	</span>
</div>

{* PHP Version - Standalone *}
<div class="check-standalone">
	<span class="icon">
		{if $results.php_version}
			<span class="icon-success">{call name="icon" icon="circle-check" size=22}</span>
		{else}
			<span class="icon-error">{call name="icon" icon="circle-x" size=22}</span>
		{/if}
	</span>
	<div class="content">
		<div class="label">PHP Version</div>
		<div class="detail {if $results.php_version}success{else}error{/if}">
			{if $results.php_version}
				PHP {$results.php_version}
			{else}
				PHP 8.2 or later is required
			{/if}
		</div>
	</div>
</div>

{* PHP Extensions Section *}
{$ext_passed = 0}
{$ext_total = 16}
{if $results.ext_session}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_curl}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_pcre}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_spl}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_ctype}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_gd}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_mailparse && $results.mailparse_version}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_mbstring}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_mysqli}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_mysqlnd}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_dom}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_xml}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_simplexml}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_json}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_openssl}{$ext_passed = $ext_passed + 1}{/if}
{if $results.ext_yaml}{$ext_passed = $ext_passed + 1}{/if}

<div class="check-section {if $ext_passed < $ext_total}open{/if}" id="section-extensions">
	<div class="check-section-header" onclick="this.parentElement.classList.toggle('open')">
		<span class="title">
			{if $ext_passed == $ext_total}
				<span class="icon-success">{call name="icon" icon="circle-check" size=20}</span>
			{else}
				<span class="icon-error">{call name="icon" icon="circle-x" size=20}</span>
			{/if}
			PHP Extensions
		</span>
		<span class="status">
			{$ext_passed}/{$ext_total} passed
			<span class="chevron">{call name="icon" icon="chevron-down" size=18}</span>
		</span>
	</div>
	<div class="check-section-content">
		<div class="check-item">
			<span class="icon {if $results.ext_ctype}icon-success{else}icon-error{/if}">
				{if $results.ext_ctype}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">ctype</span>
			{if !$results.ext_ctype}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_curl}icon-success{else}icon-error{/if}">
				{if $results.ext_curl}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">curl</span>
			{if !$results.ext_curl}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_dom}icon-success{else}icon-error{/if}">
				{if $results.ext_dom}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">dom</span>
			{if !$results.ext_dom}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_gd}icon-success{else}icon-error{/if}">
				{if $results.ext_gd}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">gd</span>
			{if !$results.ext_gd}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_json}icon-success{else}icon-error{/if}">
				{if $results.ext_json}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">json</span>
			{if !$results.ext_json}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_mailparse && $results.mailparse_version}icon-success{else}icon-error{/if}">
				{if $results.ext_mailparse && $results.mailparse_version}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">mailparse</span>
			{if !$results.ext_mailparse}
				<span class="status-text error">Required</span>
			{elseif !$results.mailparse_version}
				<span class="status-text error">Version 3.1.3+ required</span>
			{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_mbstring}icon-success{else}icon-error{/if}">
				{if $results.ext_mbstring}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">mbstring</span>
			{if !$results.ext_mbstring}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_mysqli}icon-success{else}icon-error{/if}">
				{if $results.ext_mysqli}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">mysqli</span>
			{if !$results.ext_mysqli}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_mysqlnd}icon-success{else}icon-error{/if}">
				{if $results.ext_mysqlnd}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">mysqlnd</span>
			{if !$results.ext_mysqlnd}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_openssl}icon-success{else}icon-error{/if}">
				{if $results.ext_openssl}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">openssl</span>
			{if !$results.ext_openssl}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_pcre}icon-success{else}icon-error{/if}">
				{if $results.ext_pcre}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">pcre</span>
			{if !$results.ext_pcre}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_session}icon-success{else}icon-error{/if}">
				{if $results.ext_session}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">session</span>
			{if !$results.ext_session}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_simplexml}icon-success{else}icon-error{/if}">
				{if $results.ext_simplexml}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">simplexml</span>
			{if !$results.ext_simplexml}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_spl}icon-success{else}icon-error{/if}">
				{if $results.ext_spl}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">spl</span>
			{if !$results.ext_spl}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_xml}icon-success{else}icon-error{/if}">
				{if $results.ext_xml}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">xml</span>
			{if !$results.ext_xml}<span class="status-text error">Required</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.ext_yaml}icon-success{else}icon-error{/if}">
				{if $results.ext_yaml}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">yaml</span>
			{if !$results.ext_yaml}<span class="status-text error">Required</span>{/if}
		</div>
	</div>
</div>

{* PHP Configuration Section *}
{$config_passed = 0}
{$config_total = 3}
{if $results.file_uploads}{$config_passed = $config_passed + 1}{/if}
{if $results.memory_limit}{$config_passed = $config_passed + 1}{/if}
{if $results.upload_tmp_dir}{$config_passed = $config_passed + 1}{/if}
{$has_config_warning = !$results.upload_tmp_dir}

<div class="check-section {if $config_passed < $config_total || $has_config_warning}open{/if}" id="section-config">
	<div class="check-section-header" onclick="this.parentElement.classList.toggle('open')">
		<span class="title">
			{if $config_passed == $config_total}
				{if $has_config_warning}
					<span class="icon-warning">{call name="icon" icon="circle-alert" size=20}</span>
				{else}
					<span class="icon-success">{call name="icon" icon="circle-check" size=20}</span>
				{/if}
			{else}
				<span class="icon-error">{call name="icon" icon="circle-x" size=20}</span>
			{/if}
			PHP Configuration
		</span>
		<span class="status">
			{$config_passed}/{$config_total} passed
			<span class="chevron">{call name="icon" icon="chevron-down" size=18}</span>
		</span>
	</div>
	<div class="check-section-content">
		<div class="check-item">
			<span class="icon {if $results.file_uploads}icon-success{else}icon-error{/if}">
				{if $results.file_uploads}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">file_uploads</span>
			{if !$results.file_uploads}<span class="status-text error">Must be enabled</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.memory_limit}icon-success{else}icon-error{/if}">
				{if $results.memory_limit}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-x" size=18}{/if}
			</span>
			<span class="label">memory_limit</span>
			{if !$results.memory_limit}<span class="status-text error">16M minimum (32M recommended)</span>{/if}
		</div>
		<div class="check-item">
			<span class="icon {if $results.upload_tmp_dir}icon-success{else}icon-warning{/if}">
				{if $results.upload_tmp_dir}{call name="icon" icon="circle-check" size=18}{else}{call name="icon" icon="circle-alert" size=18}{/if}
			</span>
			<span class="label">upload_tmp_dir</span>
			{if !$results.upload_tmp_dir}<span class="status-text warning">Should be set</span>{/if}
		</div>
	</div>
</div>

<form action="index.php" method="POST">
	<div class="button-row">
		{if !$fails}
			<input type="hidden" name="step" value="{$smarty.const.STEP_LICENSE}">
			<button type="submit">
				Continue
				{call name="icon" icon="arrow-right" size=18}
			</button>
		{else}
			<input type="hidden" name="step" value="{$smarty.const.STEP_ENVIRONMENT}">
			<button type="submit">
				Check Again
				{call name="icon" icon="arrow-right" size=18}
			</button>
		{/if}
	</div>
</form>
