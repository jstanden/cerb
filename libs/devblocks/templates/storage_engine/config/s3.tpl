This engine stores content in Amazon's S3 cloud storage service.<br>
<br>

<b>Access key:</b><br>
<input type="text" name="access_key" size="32" value="{$profile->params.access_key}"><br>

<b>Secret key:</b> {if $profile->params.secret_key}(leave blank for unchanged){/if}<br>
<input type="password" name="secret_key" size="64" value="" placeholder="{if $profile->params.secret_key}(saved){/if}" autocomplete="off" spellcheck="false"><br>

<b>Bucket:</b><br>
<input type="text" name="bucket" size="32" value="{$profile->params.bucket}"><br>

<b>Path prefix:</b> (optional)<br>
<input type="text" name="path_prefix" size="64" value="{$profile->params.path_prefix}" placeholder="path/to/files/"><br>

<b>Host:</b> (see: <a href="https://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region" target="_blank" rel="noreferrer noopener">AWS S3 regional endpoints</a>)<br>
<input type="text" name="host" size="64" value="{$profile->params.host}" placeholder="s3.amazonaws.com"><br>