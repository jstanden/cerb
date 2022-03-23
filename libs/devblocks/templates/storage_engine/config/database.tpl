This engine stores content in a database.<br>
<br>

<b>MySQL Host:</b><br>
<input type="text" name="host" size="64" value="{$profile->params.host}"><br>

<b>User:</b><br>
<input type="text" name="user" size="32" value="{$profile->params.user}"><br>

<b>Password:</b><br>
<input type="password" name="password" size="64" value="{$profile->params.password}" autocomplete="off" spellcheck="false"><br>

<b>Database:</b><br>
<input type="text" name="database" size="64" value="{$profile->params.database}"><br>

{*
<b>Port:</b><br>
<input type="text" name="port" size="5" value="{$profile->params.port}"><br>
*}