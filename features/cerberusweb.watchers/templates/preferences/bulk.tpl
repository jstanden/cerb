<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="1%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%"><h1>{$translate->_('common.bulk_update')|capitalize}</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmBulkWatchers" name="frmBulkWatchers">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="core.pref.notifications">
<input type="hidden" name="action" value="doWatcherBulkPanel">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<h2>{$translate->_('common.bulk_update.with')|capitalize}:</h2>

<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
<br>
<br>

<H2>{$translate->_('common.bulk_update.do')|capitalize}:</H2>

<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
		<td width="100%">
			<select name="do_status">
				<option value=""></option>
				<option value="0">{'common.enabled'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.disabled'|devblocks_translate|capitalize}</option>
				<option value="2">{'status.deleted'|devblocks_translate|capitalize}</option>
			</select>
			<button type="button" onclick="this.form.do_status.selectedIndex = 1;">{'common.enabled'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.do_status.selectedIndex = 2;">{'common.disabled'|devblocks_translate|lower}</button>
			<button type="button" onclick="this.form.do_status.selectedIndex = 3;">{'status.deleted'|devblocks_translate|lower}</button>
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true}

<br>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('frmBulkWatchers','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>