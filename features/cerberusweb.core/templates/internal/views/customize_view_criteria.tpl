{if !empty($is_custom)}
	{$view_params = $view->getParamsRequired()}
{else}
	{$view_params = $view->getEditableParams()}
	{$presets = $view->getPresets()}
{/if}
{$parent_div = "viewCustom{if !empty($is_custom)}Req{/if}Filters{$view->id}"}

<table cellpadding="2" cellspacing="0" border="0" width="100%">
{if empty($is_custom)}
<tbody class="summary">
<tr>
	<td colspan="2">
		<div class="badge badge-lightgray filters" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;">{'common.filters'|devblocks_translate|capitalize}: &#x25be;</div>
		<ul class="cerb-popupmenu cerb-float" style="margin-top:-2px;">
			<li><a href="javascript:;" onclick="$frm=$(this).closest('form');genericAjaxGet('','c=internal&a=viewToggleFilters&id={$view->id}&show=' + ($frm.find('tbody.full').toggle().is(':hidden')?'0':'1'));$(this).closest('ul.cerb-popupmenu').hide();">Toggle Advanced</a></li>
			<li><a href="javascript:;" onclick="$('#{$parent_div}').find('select[name=_preset]').val('reset').trigger('change');">{'common.reset'|devblocks_translate|capitalize}</a></li>
			{if !empty($presets)}
			<li><hr></li>
			<li><b>Presets</b></li>
			{foreach from=$presets item=preset key=preset_id}
			<li><a href="javascript:;" onclick="$('#{$parent_div}').find('select[name=_preset]').val('{$preset_id}').trigger('change');">{$preset->name}</a></li>
			{/foreach}
			{/if}
		</ul>
		<script type="text/javascript">
		$('#{$parent_div} TBODY.summary > TR > TD:first > div.filters')
			.click(function(e) {
				$(this).next('ul.cerb-popupmenu').show();
			})
			.closest('td')
			.hover(function(e){},
				function(e) {
					$(this).find('ul.cerb-popupmenu').hide();
				}
			)
			.find('ul.cerb-popupmenu li')
			.click(function(e) {
				if($(e.target).is('a'))
					return;
				$(this).find('a').click();
			})
			;
		</script>
		
		{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params readonly=true}
		<script type="text/javascript">
		$('#{$parent_div} TBODY.summary TD:first').hover(
			function() {
				$(this).find('a.delete').show();
			},
			function() {
				$(this).find('a.delete').hide();
			}
		);
		</script>
	</td>
</tr>
</tbody>
{/if}{* empty($is_custom) *}

<tbody class="full" style="width:100%;display:{if $is_custom || $view->renderFilters};{else}none;{/if}">
<tr>
	<td width="60%" valign="top">
		<fieldset>
			<legend>{$translate->_('common.filters')|capitalize}</legend>
			
			{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params}
			
			<div style="margin-top:5px;">
				<select name="_preset" onchange="$val=$(this).val();if(0==$val.length)return;if('reset'==$val) { var $form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewResetFilters'); return; } if('remove'==$val) { var $form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewAddFilter{if $is_custom}&is_custom=1{/if}'); return; } if('edit'==$val) { $(this).val('');$('#divRemovePresets{$view->id}').fadeIn();return; } if('add'==$val) { $(this).val('');$('#divAddPreset{$view->id}').fadeIn().find('input:text:first').focus();return; } var $form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewLoadPreset');">
					<option value="">-- action --</option>
					<optgroup label="Filters">
						{if !empty($view_params)}<option value="remove">Remove selected filters</option>{/if}
						{if !$is_custom}
						<option value="reset">Reset filters</option>
						{if !empty($view_params)}<option value="add">Save filters as preset</option>{/if}
						{/if}
					</optgroup>
					
					{if !$is_custom && !empty($presets)}
					<optgroup label="All Presets">
						{foreach from=$presets item=preset key=preset_id}
						<option value="{$preset_id}">{$preset->name}</option>
						{/foreach}
						<option value="edit">(edit presets)</option>
					</optgroup>
					{/if}
				</select>
				
				{if !$is_custom}
				<div id="divAddPreset{$view->id}" class="block" style="display:none;margin:5px;">
					<b>Save filters as preset:</b><br>
					{if !empty($presets)}
					<select name="_preset_replace" onchange="if(''==$(this).val()) { $(this).siblings('input:text[name=_preset_name]').val('').focus(); } else { $(this).siblings('input:text[name=_preset_name]').val($(this).find('option:selected').text()).focus(); } ">
						<option value="" selected="selected">- new preset: -</option>
						{foreach from=$presets item=preset key=preset_id}
						<option value="{$preset_id}">{$preset->name}</option>
						{/foreach}
					</select>
					{/if}
					<input type="text" name="_preset_name" size="32" value="">
					<br>
					<br>
					<button type="button" onclick="var $form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewAddPreset');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
					<a href="javascript:;" onclick="$(this).closest('div').fadeOut();"> {$translate->_('common.cancel')|lower}</a>
				</div>
				<div id="divRemovePresets{$view->id}" class="block" style="display:none;margin:5px;">
					<b>Remove these presets:</b><br>
					{foreach from=$presets item=preset key=preset_id}
					<label><input type="checkbox" name="_preset_del[]" value="{$preset_id}"> {$preset->name}</label><br>
					{/foreach}
					<br>
					<button type="button" onclick="var $form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewEditPresets');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
					<a href="javascript:;" onclick="$(this).closest('div').fadeOut();"> {$translate->_('common.cancel')|lower}</a>
				</div>
				{/if}
			</div>
		</fieldset>
	</td>
	
	<td valign="top" width="40%">
		<fieldset>
			<legend>Add Filter</legend>
			
			<b>{$translate->_('common.field')|capitalize}:</b><br>
			<blockquote style="margin:5px;">
				{$searchable_fields = $view->getParamsAvailable()}
				{$has_custom = false}
				
				<select name="field" onchange="genericAjaxGet('add{$parent_div}','c=internal&a=viewGetCriteria&id={$view->id}&field='+selectValue(this));">
					<option value="">-- choose --</option>
					{foreach from=$searchable_fields item=column key=token}
						{if substr($token,0,3) != "cf_"}
							{if !empty($column->db_label) && !empty($token)}
							<option value="{$token}">{$column->db_label|capitalize}</option>
							{/if}
						{else}
							{$has_custom = true}
						{/if}
					{/foreach}
					
					{if $has_custom}
					<optgroup label="Custom Fields">
					{foreach from=$searchable_fields item=column key=token}
						{if substr($token,0,3) == "cf_"}
							{if !empty($column->db_label) && !empty($token)}
							<option value="{$token}">{$column->db_label|capitalize}</option>
							{/if}
						{/if}
					{/foreach}
					</optgroup>
					{/if}
				</select>
			</blockquote>
		
			<div id="add{$parent_div}" style="background-color:rgb(255,255,255);"></div>
			<button type="button" onclick="$form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewAddFilter{if $is_custom}&is_custom=1{/if}');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Add Filter</button>
		</fieldset>
	</td>
</tr>
</tbody>
</table>
