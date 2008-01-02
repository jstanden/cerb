{if !empty($view->params)}
<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="POST" name="{$view->id}_criteriaForm">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewRemoveCriteria">
<input type="hidden" name="id" value="{$view->id}">
<input type="hidden" name="response_uri" value="{$response_uri}">
<input type="hidden" name="field" value="">
<table cellpadding="2" cellspacing="0" width="200" border="0">
	<tr>
		<td nowrap="nowrap">
			<h2 style="display:inline;">Current Criteria</h2>
			[ <a href="javascript:;" onclick="document.{$view->id}_criteriaForm.a.value='viewResetCriteria';document.{$view->id}_criteriaForm.submit();">reset</a> ]
		</td>
	</tr>
	<tr>
		<td>
			<table cellpadding="2" cellspacing="0" border="0">
				{if !empty($view->params)}
				{foreach from=$view->params item=param}
				{assign var=field value=$param->field}
					<tr>
						<td width="100%">
						<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
						{$translate->_($view_fields.$field->db_label)|capitalize} 
						{$param->operator}
						<b>{$view->renderCriteriaParam($param)}</b>
						</td>
						<td width="0%" nowrap="nowrap" valign="top"><a href="javascript:;" onclick="document.{$view->id}_criteriaForm.field.value='{$param->field}';document.{$view->id}_criteriaForm.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_error.gif{/devblocks_url}" border="0" align="absmiddle"></a></td>
					</tr>
				{/foreach}
				{/if}
			</table>
		</td>
	</tr>
</table>
</form>
</div>
<br>
{/if}

<div class="block">
	<form action="{devblocks_url}{/devblocks_url}" method="POST">
	<input type="hidden" name="c" value="internal">
	<input type="hidden" name="a" value="viewAddCriteria">
	<input type="hidden" name="id" value="{$view->id}">
	<input type="hidden" name="response_uri" value="{$response_uri}">
	
	<h2>Add Criteria</h2>
	<b>Field:</b><br>
	<blockquote style="margin:5px;">
		<select name="field" onchange="genericAjaxGet('addCriteriaOptions','c=internal&a=viewGetCriteria&id={$view->id}&field='+selectValue(this));toggleDiv('addCriteriaSave',(selectValue(this)!='')?'block':'none');">
			<option value="">-- choose --</option>
			
			<optgroup label="Ticket">
			{foreach from=$view_searchable_fields item=column key=token}
				{if substr($token,0,3) != "cf_"}
					{if !empty($column->db_label) && !empty($token)}
					<option value="{$token}">{$translate->_($column->db_label)|capitalize}</option>
					{/if}
				{/if}
			{/foreach}
			</optgroup>
			
			<optgroup label="Custom Fields">
			{foreach from=$view_searchable_fields item=column key=token}
				{if substr($token,0,3) == "cf_"}
					{if !empty($column->db_label) && !empty($token)}
					<option value="{$token}">{$translate->_($column->db_label)|capitalize}</option>
					{/if}
				{/if}
			{/foreach}
			</optgroup>
		</select>
	</blockquote>

	<div id="addCriteriaOptions" style="background-color:rgb(255,255,255);"></div>
	<div id="addCriteriaSave" style="display:none;"><br><button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button></div>
	
	</form>
</div>