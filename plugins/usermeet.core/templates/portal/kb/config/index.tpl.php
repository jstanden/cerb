<b>URL to Logo:</b> (link to image, default if blank)<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>Page Title:</b> (default if blank)<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>CAPTCHA:</b> (displays a CAPTCHA image in the form to help block automated spam)<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked{/if}> Enabled</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked{/if}> Disabled</label>
<br>
<br>

<div class="subtle2">

<h2>Editors</h2>
These are users who can log in and modify articles through the public interface.<br>
<br>

{if !empty($editors)}
	<table cellpadding="2" cellspacing="1" border="0">
	<tr>
		<td align="left"><b>Email</b></td>
		<td align="center"><b>Change Password</b></td>
		<td align="center"><b>Delete</b></td>
	</tr>
	{foreach from=$editors item=editor name=editors}
		<tr>
			<td><input type="hidden" name="editors_email[]" value="{$editor.email}">{$editor.email}</td>
			<td><input type="password" name="editors_pass[]" size="16" value=""></td>
			<td align="center"><input type="checkbox" name="editors_delete[]" value="{$editor.email}"></td>
		</tr>
		<br>
	{/foreach}
	</table>
{else}
	<i>No editors have been created.</i><br>
	<br>
{/if}

<h2>Add Editor Account</h2>

<b>E-mail Address:</b><br>
<input type="text" size="65" name="editor_email" value=""><br>
<br>

<b>Password:</b><br>
<input type="password" size="16" name="editor_pass" value=""><br>
<br>

</div>