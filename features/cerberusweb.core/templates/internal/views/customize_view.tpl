<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewSaveCustomize">
<input type="hidden" name="id" value="{$view->id}">
<div class="block" style="margin:5px;">
<h1>{$translate->_('common.customize')|capitalize}</h1>

{* Custom Views *}
{if substr($view->id,0,5)=="cust_"}
	{assign var=is_custom value=true}
{else}
	{assign var=is_custom value=false}
{/if}

{if $is_custom}
<b>List Title:</b><br>
<input type="text" name="title" value="{$view->name}" size="64" autocomplete="off"><br>
<br>
{/if}

<b>{$translate->_('dashboard.columns')|capitalize}:</b> 
 &nbsp; 
<a href="javascript:;" onclick="Devblocks.resetSelectElements('customize{$view->id}','columns[]');">{$translate->_('common.clear')|lower}</a>
<br>
{$columnsAvailable = $view->getColumnsAvailable()}
{section start=0 step=1 loop=15 name=columns}
{assign var=index value=$smarty.section.columns.index}
<div class="column"> 
<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>
<select name="columns[]">
	<option value=""></option>
	
	{foreach from=$columnsAvailable item=colAvail}
		{if substr($colAvail->token,0,3) != "cf_"}
			{if !empty($colAvail->db_label) && !empty($colAvail->token)}
				<option value="{$colAvail->token}" {if $view->view_columns.$index==$colAvail->token}selected{/if}>{$colAvail->db_label|capitalize}</option>
			{/if}
		{else}
			{assign var=has_custom value=1}
		{/if}
	{/foreach}
	
	{if $has_custom}
	<optgroup label="Custom Fields">
	{foreach from=$columnsAvailable item=colAvail}
		{if substr($colAvail->token,0,3) == "cf_"}
			{$field_id = substr($colAvail->token,3)}
			{if isset($custom_fields.$field_id)}
				{$field = $custom_fields.$field_id}
				{if empty($field->group_id) || $active_worker->isTeamMember($field->group_id)}
					{if !empty($colAvail->db_label) && !empty($colAvail->token)}
					<option value="{$colAvail->token}" {if $view->view_columns.$index==$colAvail->token}selected{/if}>{$colAvail->db_label|capitalize}</option>
					{/if}
				{/if}
			{/if}
		{/if}
	{/foreach}
	</optgroup>
	{/if}
</select>
</div>
{/section}
<br>
<b>{$translate->_('dashboard.num_rows')|capitalize}:</b> <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}"><br>
<br>

{if $is_custom}
<b>Criteria:</b><br>
<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl"}
</div>
<br>
{/if}

<button type="button" onclick="this.form.a.value='viewSaveCustomize';genericAjaxPost('customize{$view->id}','view{$view->id}','c=internal');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="toggleDiv('customize{$view->id}','none');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>

<br>
<br>
</div>

<script type="text/javascript">
	$('#customize{$view->id}').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
</script>