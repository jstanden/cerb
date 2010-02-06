<form action="{devblocks_url}{/devblocks_url}" method="post" target="_blank" id="frm{$view_id}_export">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoExport">
<input type="hidden" name="view_id" value="{$view_id}">

<H3>{$translate->_('common.export')|capitalize}</H3>
<br>

<b>Columns:</b>
 &nbsp; 
<a href="javascript:;" onclick="Devblocks.resetSelectElements('frm{$view_id}_export','columns[]');">{$translate->_('common.clear')|capitalize}</a>
<br>
{section start=0 step=1 loop=15 name=columns}
{assign var=index value=$smarty.section.columns.index}
{math equation="x+1" x=$index format="%02d"}: 
<select name="columns[]">
	<option value=""></option>
	
	{foreach from=$model_columns item=model_column}
		{if substr($model_column->token,0,3) != "cf_"}
			{if !empty($model_column->db_label) && !empty($model_column->token)}
				<option value="{$model_column->token}" {if $view->view_columns.$index==$model_column->token}selected{/if}>{$model_column->db_label}</option>
			{/if}
		{else}
			{assign var=has_custom value=1}
		{/if}
	{/foreach}
	
	{if $has_custom}
	<optgroup label="Custom Fields">
	{foreach from=$model_columns item=model_column}
		{if substr($model_column->token,0,3) == "cf_"}
			{if !empty($model_column->db_label) && !empty($model_column->token)}
			<option value="{$model_column->token}" {if $view->view_columns.$index==$model_column->token}selected{/if}>{$model_column->db_label}</option>
			{/if}
		{/if}
	{/foreach}
	</optgroup>
	{/if}
	
</select>
<br>
{/section}
<br>

<b>Export List As:</b><br>
<select name="export_as">
	<option value="csv" selected="selected">Comma-separated values (.csv)</option>
	<option value="xml">XML (.xml)</option>
	{*<option value="xls">Excel 2003 (.xls)</option>*}
</select>
<br>

<br>
<button type="button" onclick="this.form.submit();" style=""><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" border="0" align="top"> {$translate->_('common.export')|capitalize}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style=""><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Cancel</button>

</form>