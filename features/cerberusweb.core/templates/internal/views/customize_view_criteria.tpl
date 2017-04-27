{if $is_custom}
	{$view_params = $view->getParamsRequired()}
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
		<ul class="cerb-popupmenu cerb-float" style="margin-top:-2px;">
			<li><a href="javascript:;" onclick="$frm=$(this).closest('form');genericAjaxGet('','c=internal&a=viewToggleFilters&id={$view->id}&show=' + ($frm.find('tbody.full').toggle().is(':hidden')?'0':'1'));$(this).closest('ul.cerb-popupmenu').hide();">Toggle Helper</a></li>
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
{/if}

<tbody class="full" style="width:100%;display:{if $is_custom || $view->renderFilters};{else}none;{/if}">
<tr>
	<td valign="top" width="100%">
		{if $is_custom}
		<div class="cerb-filters-list" style="position:relative;margin-bottom:10px;">
			{include file="devblocks:cerberusweb.core::internal/views/criteria_list_params.tpl" params=$view_params}
		</div>
		{/if}
	
		<fieldset class="black">
			<legend>Add Filters</legend>
			
			<div>
				<label><input type="radio" name="add_mode" value="query" {if $add_mode != 'filters'}checked="checked"{/if}> {'common.query'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="add_mode" value="filters" {if $add_mode == 'filters'}checked="checked"{/if}> {'common.list'|devblocks_translate|capitalize}</label>
			</div>
			
			<div class="cerb-filter-mode-query" {if $add_mode == 'filters'}style="display:none;"{/if}>
				<div>
					<input type="text" name="query" style="width:100%;" data-context="{$view->getContext()}" data-query="">
				</div>
			</div>
			
			<div class="cerb-filter-mode-list" {if $add_mode != 'filters'}style="display:none;"{/if}>
			<blockquote style="margin:5px;">
				{$searchable_fields = $view->getParamsAvailable(true)}
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
			</div>
		</fieldset>
		
		<button type="button" onclick="$form_id = $(this).closest('form').attr('id'); if(0==$form_id.length)return;genericAjaxPost($form_id,'{$parent_div}','c=internal&a=viewAddFilter&replace=1{if $is_custom}&is_custom=1{/if}');"><span class="glyphicons glyphicons-circle-plus"></span> Update Filters</button>
	</td>
</tr>
</tbody>
</table>

<script type="text/javascript">
$(function() {
	var $parent = $('#{$parent_div}');
	var $query = $parent.find('input[name=query]').cerbQueryTrigger();
	var $mode_query = $parent.find('div.cerb-filter-mode-query');
	var $mode_list = $parent.find('div.cerb-filter-mode-list');
	
	$parent.find('input[name=add_mode]').click(function(e) {
		e.stopPropagation();
		
		var mode = $(this).val();
		
		if(mode == 'query') {
			$mode_query.fadeIn();
			$mode_list.hide();
		} else {
			$mode_query.hide();
			$mode_list.fadeIn();
		}
	});
	
	$query.on('cerb-query-saved', function(e) {
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
});
</script>