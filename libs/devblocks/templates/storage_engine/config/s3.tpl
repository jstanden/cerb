This engine stores content in an S3-compatible storage service.<br>
<br>

<b>AWS credentials:</b><br>

<div>
	<button type="button" class="chooser-abstract" data-field-name="connected_account_id" data-context="{$connected_account_context}" data-single="true" data-query="service:(type:aws)"><span class="cerb-icons cerb-icon-search"></span></button>

	<ul class="bubbles chooser-container">
		{if $connected_account}
			<li><input type="hidden" name="connected_account_id" value="{$connected_account->id}"><a class="cerb-peek-trigger no-underline" data-context="{$connected_account_context}" data-context-id="{$connected_account->id}">{$connected_account->name}</a></li>
		{/if}
	</ul>
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