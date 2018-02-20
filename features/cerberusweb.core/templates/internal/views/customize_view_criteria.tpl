{$view_params = []}
{if $is_custom}
	{* Get required params only from parent worklist *}
	{$workspace_list = $view->getCustomWorklistModel()}
	{if $workspace_list}
	{$view_params = $workspace_list->getParamsRequired()}
	{/if}
{else}
	{$view_params = $view->getEditableParams()}
	{$presets = $view->getPresets()}
{/if}
{$parent_div = "viewCustom{if $is_custom}Req{/if}Filters{$view->id}"}

<table cellpadding="2" cellspacing="0" border="0" width="100%">
{if !$is_custom}
<tbody class="summary">
<tr>
	<td>
		<div class="badge badge-lightgray filters" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;">{'common.filters'|devblocks_translate|capitalize}: <span class="glyphicons glyphicons-chevron-down" style="font-size:12px;"></span></div>
		
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
{/if}

<tbody class="full" style="width:100%;display:{if $is_custom};{else}none;{/if}">
<tr>
	<td valign="top" width="100%">
		{if $is_custom}
		<div class="cerb-filters-list" style="position:relative;margin-bottom:10px;">
			{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params}
		</div>
		{/if}

		{if !$is_custom}
		<fieldset class="black">
			<legend>{'common.filter.add'|devblocks_translate|capitalize}</legend>
			<input type="hidden" name="add_mode" value="filters">
			
			<div class="cerb-filter-mode-list">
			<blockquote style="margin:5px;">
				{$searchable_fields = $view->getParamsAvailable(true)}
				{$has_custom = false}
				
				<select name="field" onchange="genericAjaxGet('add{$parent_div}','c=internal&a=viewGetCriteria&id={$view->id}&field='+selectValue(this));">
					<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
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
			</div>
			
			<button type="button" class="cerb-save"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
	</td>
</tr>
</tbody>
</table>

<script type="text/javascript">
$(function() {
	var $parent = $('#{$parent_div}');
	
	$parent.find('TBODY.summary > TR > TD:first > div.filters').on('click', function(e) {
		e.stopPropagation();
		
		var $frm = $parent.closest('form');
		$frm.find('tbody.full').toggle();
	});
	
	$parent.find('div.cerb-filters-list').on('click', function(e) {
		e.stopPropagation();
		var $target = $(e.target);
		
		if(!$target.is('span'))
			return;
		
		var $container = $target.closest('div');
		var $checkbox = $container.find('input:checkbox');
		
		if($checkbox.prop('checked')) {
			$checkbox.prop('checked', false);
			$target.css('color', '');
			$container.css('text-decoration', '');
			
		} else {
			$checkbox.prop('checked', true);
			$target.css('color', 'rgb(150,0,0)');
			$container.css('text-decoration', 'line-through');
		}
	});
	
	$parent.find('button.cerb-save').on('click', function(e) {
		e.stopPropagation();
		
		var $form_id = $(this).closest('form').attr('id');
		
		if(0==$form_id.length)
			return;
		
		genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewAddFilter&replace=1{if $is_custom}&is_custom=1{/if}');
	});
});
</script>