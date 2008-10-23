<table cellpadding="2" cellspacing="0" border="0" width="97%">
<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div class="block" style="width:300px;">
		<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td><h2>{$translate->_('common.filters')|capitalize}</h2></td>
			<td>{$translate->_('common.clear')|capitalize}</td>
		</tr>
		{foreach from=$view->params item=param}
			<tr>
			<td width="100%">
				{assign var=field value=$param->field}
				<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="absmiddle"> 
				{$view_fields.$field->db_label|capitalize} 
				{$param->operator}
				<b>{$view->renderCriteriaParam($param)}</b>
			</td>
			<td width="0%" nowrap="nowrap" valign="top" align="middle"><input type="checkbox" name="field_deletes[]" value="{$param->field}"></td>
			</tr>
		{/foreach}
		</table>
		<button type="button" onclick="this.form.a.value='viewAddFilter';genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_error.gif{/devblocks_url}" align="top"> Delete</button>
		</div>
	</td>
	<td valign="top" width="100%">
		<div class="block" style="width:98%;">
			<h2>Add Filter</h2>
			<b>{$translate->_('common.field')|capitalize}:</b><br>
			<blockquote style="margin:5px;">
				<select name="field" onchange="genericAjaxGet('addCriteria{$view->id}','c=internal&a=viewGetCriteria&id={$view->id}&field='+selectValue(this));">
					<option value="">-- choose --</option>
					
					{foreach from=$view_searchable_fields item=column key=token}
						{if substr($token,0,3) != "cf_"}
							{if !empty($column->db_label) && !empty($token)}
							<option value="{$token}">{$column->db_label|capitalize}</option>
							{/if}
						{/if}
					{/foreach}
					
					<optgroup label="Custom Fields">
					{foreach from=$view_searchable_fields item=column key=token}
						{if substr($token,0,3) == "cf_"}
							{if !empty($column->db_label) && !empty($token)}
							<option value="{$token}">{$column->db_label|capitalize}</option>
							{/if}
						{/if}
					{/foreach}
					</optgroup>
				</select>
			</blockquote>
		
			<div id="addCriteria{$view->id}" style="background-color:rgb(255,255,255);"></div>
			<button type="button" onclick="this.form.a.value='viewAddFilter';genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_new.gif{/devblocks_url}" align="top"> Add Filter</button>
		</div>		
	</td>
</tr>
</table>
