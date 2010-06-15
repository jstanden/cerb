<table cellpadding="2" cellspacing="0" border="0" width="97%">
<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div class="block" style="width:300px;">
			<h2>{$translate->_('common.filters')|capitalize}</h2>
			<table cellpadding="2" cellspacing="0" border="0">
			{include file="file:$core_tpl/internal/views/criteria_list_params.tpl" params=$view->params batchDelete=true}
			</table>
			
			<div style="margin-top:2px;">
				<select name="_preset" onchange="$val=$(this).val();if(0==$val.length)return;if('reset'==$val) { genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewResetFilters'); return; } if('remove'==$val) { genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewAddFilter'); return; } if('edit'==$val) { $(this).val('');$('#divRemovePresets{$view->id}').fadeIn();return; } if('add'==$val) { $(this).val('');$('#divAddPreset{$view->id}').fadeIn().find('input:text:first').focus();return; } genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewLoadPreset');">
					<option value="">-- action --</option>
					<optgroup label="Filters">
						{if !empty($view->params)}<option value="remove">Remove selected filters</option>{/if}
						<option value="reset">Reset filters</option>
						{if !empty($view->params)}<option value="add">Save filters as preset</option>{/if}
					</optgroup>
					{$presets = $view->getPresets()}
					{if !empty($presets)}
					<optgroup label="All Presets">
						{foreach from=$presets item=preset key=preset_id}
						<option value="{$preset_id}">{$preset->name|escape}</option>
						{/foreach}
						<option value="edit">(edit presets)</option>
					</optgroup>
					{/if}
				</select>
				<div id="divAddPreset{$view->id}" class="block" style="display:none;margin:5px;">
					<b>Save filters as preset:</b><br>
					<input type="text" name="_preset_name" size="32" value="">
					<br>
					<button type="button" onclick="genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewAddPreset');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
					<a href="javascript:;" onclick="$(this).closest('div').fadeOut();"> {$translate->_('common.cancel')|lower}</a>
				</div>
				<div id="divRemovePresets{$view->id}" class="block" style="display:none;margin:5px;">
					<b>Remove these presets:</b><br>
					{foreach from=$presets item=preset key=preset_id}
					<label><input type="checkbox" name="_preset_del[]" value="{$preset_id}"> {$preset->name|escape}</label><br>
					{/foreach}
					<br>
					<button type="button" onclick="genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewEditPresets');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
					<a href="javascript:;" onclick="$(this).closest('div').fadeOut();"> {$translate->_('common.cancel')|lower}</a>
				</div>
			</div>
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
			<button type="button" onclick="genericAjaxPost('customize{$view->id}','viewCustomFilters{$view->id}','c=internal&a=viewAddFilter');"><span class="cerb-sprite sprite-add"></span> Add Filter</button>
		</div>		
	</td>
</tr>
</table>
