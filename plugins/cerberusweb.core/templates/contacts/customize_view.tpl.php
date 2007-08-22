<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewSaveCustomize">
<input type="hidden" name="id" value="{$view->id}">
<div style="background-color: #EEEEEE;padding:5px;">
<h1>{$translate->_('common.customize')|capitalize}</h1>

<b>{$translate->_('dashboard.columns')|capitalize}:</b><br>
{section start=0 step=1 loop=15 name=columns}
{assign var=index value=$smarty.section.columns.index}
{math equation="x+1" x=$index format="%02d"}: 
<select name="columns[]">
	<option value=""></option>
	{foreach from=$optColumns item=optColumn}
		{if $optColumn->token=="a_contact_org_id"}
		{elseif $optColumn->token=="a_id"}
		{elseif $optColumn->token=="c_id"}
		{else}
		<option value="{$optColumn->token}" {if $view->view_columns.$index==$optColumn->token}selected{/if}>{$optColumn->db_label|capitalize}</option>
		{/if}
	{/foreach}
</select>
<br>
{/section}
<br>
<b>{$translate->_('dashboard.num_rows')|capitalize}:</b> <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}"><br>
<br>

<input type="button" value="{$translate->_('common.save_changes')|capitalize}" onclick="genericAjaxPost('customize{$view->id}','view{$view->id}','c=contacts');">
<input type="button" value="{$translate->_('common.cancel')|capitalize}" onclick="toggleDiv('customize{$view->id}','none');">
<br>
<br>
</div>