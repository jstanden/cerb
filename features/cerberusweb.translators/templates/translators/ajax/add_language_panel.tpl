<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddLanguage">
<input type="hidden" name="c" value="translators">
<input type="hidden" name="a" value="saveAddLanguagePanel">

<h1>{$translate->_('translators.languages')|capitalize}</h1>

{if is_array($codes) && count($codes) > 1}
<div style="margin:5px;padding:5px;height:100px;border:1px solid rgb(200,200,200);background-color:rgb(250,250,250);overflow:auto;">
<table cellspacing="0" cellpadding="2" border="0">
<tr>
	<td><b>{$translate->_('translators.language')|capitalize}</td>
	<td style="padding-left:5px;"><b>Remove</td>
</tr>
{if is_array($codes)}
{foreach from=$codes key=code item=lang_name}
{if 'en_US' != $code}
<tr>
	<td>{$lang_name}</td>
	<td style="padding-left:5px;"><input type="checkbox" name="del_lang_ids[]" value="{$code}"></td>
</tr>
{/if}
{/foreach}
{/if}
</table>
</div>
{/if}

<div style="margin:5px;padding:5px;height:100px;border:1px solid rgb(200,200,200);background-color:rgb(250,250,250);overflow:auto;">
	<b>Add New Translation:</b><br>
	<select name="add_lang_code">
	<option value=""></option>
	{if is_array($locales)}
	{foreach from=$locales key=code item=label}
		{if !isset($codes.$code)}
		<option value="{$code}">{$label}</option>
		{/if}
	{/foreach}
	{/if}
	</select>
	<br>
	<br>
	
	<b>Copy New Text From:</b> <i>(e.g.: U.S. English to British English)</i><br>
	<select name="copy_lang_code">
	<option value"">-- leave blank --</option>
	{if is_array($codes)}
	{foreach from=$codes key=code item=lang_name}
		<option value="{$code}">{$lang_name}</option>
	{/foreach}
	{/if}
	</select>
	<br>
	<br>
</div>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

</form>

