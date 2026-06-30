This engine stores content in an S3-compatible storage service.<br>
<br>

<b>AWS credentials:</b><br>

<div class="cerb-ui-record-chooser" id="s3AccountChooser">
	{if $connected_account}
		<li data-context-id="{$connected_account->id}" data-label="{$connected_account->name}"></li>
	{/if}
</div>
<br>

<b>Bucket:</b><br>
<input type="text" name="bucket" size="32" value="{$profile->params.bucket}"><br>
<br>

<b>Path prefix:</b> (optional)<br>
<input type="text" name="path_prefix" size="64" value="{$profile->params.path_prefix}" placeholder="path/to/files/"><br>
<br>

<b>Host:</b> (optional scheme/port for S3-compatible services; see: <a href="https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region" target="_blank" rel="noreferrer noopener">AWS S3 regional endpoints</a>)<br>
<input type="text" name="host" size="64" value="{$profile->params.host}" placeholder="s3.amazonaws.com  —  or  http://minio.local:9000"><br>
<br>

<b>Region:</b> (optional; default <code>us-east-1</code>)<br>
<input type="text" name="region" size="32" value="{$profile->params.region}" placeholder="us-east-1"><br>
<br>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
(function() {literal}{{/literal}
	if(!(window.CerbUI && CerbUI.RecordChooser)) return;
	var el = document.getElementById('s3AccountChooser');
	if(el) new CerbUI.RecordChooser(el, { context: '{$connected_account_context}', name: 'connected_account_id', emptyIcon: 'key', query: 'service:(type:aws)' });
})();
</script>
