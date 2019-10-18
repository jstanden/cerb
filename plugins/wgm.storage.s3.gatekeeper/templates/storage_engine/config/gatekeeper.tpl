This engine stores content in Amazon S3 but protects credentials by signing requests through an external gatekeeper service.<br>
<br>

<b>URL to Gatekeeper script:</b><br>
<input type="url" name="url" size="64" value="{$profile->params.url}"><br>

<b>Login:</b><br>
<input type="text" name="username" size="32" value="{$profile->params.username}"><br>

<b>Password:</b><br>
<input type="password" name="password" size="32" value="{$profile->params.password}"><br>

<b>Bucket:</b><br>
<input type="text" name="bucket" size="32" value="{$profile->params.bucket}"><br>

<b>Path prefix:</b> (optional)<br>
<input type="text" name="path_prefix" size="64" value="{$profile->params.path_prefix}"><br>
