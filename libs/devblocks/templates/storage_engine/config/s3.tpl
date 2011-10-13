This engine stores content in Amazon's S3 cloud storage service.<br>
<br>

<b>Access key:</b><br>
<input type="text" name="access_key" size="32" value="{$profile->params.access_key}"><br>

<b>Secret key:</b><br>
<input type="password" name="secret_key" size="32" value="{$profile->params.secret_key}"><br>

<b>Bucket:</b><br>
<input type="text" name="bucket" size="16" value="{$profile->params.bucket}"><br>

<b>Host:</b>(optional; defaults to s3.amazonaws.com)<br>
<input type="text" name="host" size="16" value="{$profile->params.host}"><br>