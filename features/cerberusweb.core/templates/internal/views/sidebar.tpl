<fieldset>
	<legend class="cerb-menu">
		<a href="javascript:;" class="menu">{if isset($subtotal_fields.{$view->renderSubtotals})}{$subtotal_fields.{$view->renderSubtotals}->db_label|capitalize}{else}{'common.subtotals'|devblocks_translate|capitalize}{/if}</a> &#x25be;
	</legend>
	<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
		{foreach from=$subtotal_fields item=field_model key=field_key}
		<li><a href="javascript:;" onclick="genericAjaxGet('view{$view_id}_sidebar','c=internal&a=viewSubtotal&category={$field_key}&view_id={$view_id}');">{$field_model->db_label|capitalize}</a></li>
		{/foreach}
	</ul>

	<table cellspacing="0" cellpadding="2" border="0">
	{foreach from=$subtotal_counts item=category}
		<tr>
			<td style="padding-right:10px;" nowrap="nowrap" valign="top">
				{if !empty($category.filter)}<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', '{$category.filter.field}', '{$category.filter.oper}', { {foreach from=$category.filter.values name=values item=value key=key}'{$key}':'{$value}'{if !$smarty.foreach.values.last},{/if}{/foreach} } );">{/if}
				<span style="font-weight:bold;" title="{$category.label}">{$category.label|truncate:25}</span>
				{if !empty($category.filter)}</a>{/if}
			</td>
			<td align="right" nowrap="nowrap" valign="top">
				<div class="badge">{$category.hits}</div>
			</td>
		</tr>
		{if isset($category.children) && !empty($category.children)}
		{foreach from=$category.children item=subcategory}
		<tr>
			<td style="padding-left:10px;padding-right:10px;" nowrap="nowrap" valign="top">
				{if !empty($subcategory.filter)}<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', '{$subcategory.filter.field}', '{$subcategory.filter.oper}', { {foreach from=$subcategory.filter.values name=values item=value key=key}'{$key}':'{$value}'{if !$smarty.foreach.values.last},{/if}{/foreach} } );">{/if}
				<span>{$subcategory.label}</span>
				{if !empty($subcategory.filter)}</a>{/if}
			</td>
			<td align="right" nowrap="nowrap" valign="top">
				<div class="badge badge-lightgray">{$subcategory.hits}</div>
			</td>
		</tr>
		{/foreach}
		{/if}
	{/foreach}
	</table>	
	
</fieldset>

<script type="text/javascript">
$legend = $('#view{$view_id}_sidebar fieldset:first legend');
	$legend
		.hoverIntent({
			sensitivity:10,
			interval:300,
			over:function(e) {
				$(this).next('ul:first').show();
			},
			timeout:0,
			out:function(e){}
		})
		.closest('fieldset')
			.hover(
				function(e) {},
				function(e) {
					$(this).find('ul:first').hide();
				}
			)
		.find('ul:first > li')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li'))
					return;

				$(this).find('a').trigger('click');
			})
		;
	$legend
		.closest('fieldset')
		.find('TBODY > TR')
		.css('cursor','pointer')
		.hover(
			function(e) {
				$(this).css('background-color','rgb(255,255,200)');
			},
			function(e) {
				$(this).css('background','none');
			}
		)
		.click(function(e) {
			e.stopPropagation();
			if($(e.target).is('a'))
				return;

			$(this).find('a').trigger('click');
		})
		;
</script>