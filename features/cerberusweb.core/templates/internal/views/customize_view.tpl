<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewSaveCustomize">
<input type="hidden" name="id" value="{$view->id}">
<div class="block" style="margin:5px;">
<h1 style="margin-bottom:10px;">{'common.customize'|devblocks_translate|capitalize}</h1>

{* Custom Views *}
{$is_custom = $view->isCustom()}

{* Trigger Views *}
{if substr($view->id,0,9)=="_trigger_"}
	{$is_trigger = true}
{else}
	{$is_trigger = false}
{/if}

{if $is_custom || $is_trigger}
<fieldset class="peek peek-noborder black">
	<legend>{'common.title'|devblocks_translate|capitalize}</legend>
	
	<input type="text" name="title" value="{$view->name}" size="64" autocomplete="off"><br>
</fieldset>
{/if}

<fieldset class="peek peek-noborder black">
	<legend>
		{'dashboard.columns'|devblocks_translate|capitalize} (<a href="javascript:;" onclick="$(this).closest('fieldset').find('input:checkbox').removeAttr('checked');">{'common.clear'|devblocks_translate|lower}</a>)</b>
	</legend>

	{foreach from=$columns item=column}
	<div class="column">
	<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>
	<label><input type="checkbox" name="columns[]" value="{$column->token}" {if in_array($column->token, $view->view_columns)}checked="checked"{/if}> {$column->db_label|capitalize}</label>
	</div>
	{/foreach}
</fieldset>

<fieldset class="peek peek-noborder black">
	<legend>{'common.options'|devblocks_translate|capitalize}</legend>
	
	<div>
		{'dashboard.num_rows'|devblocks_translate}: <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}">
	</div>
		
	<div>
		{$view->renderCustomizeOptions($is_custom)}
	</div>
</fieldset>

{if $is_custom}
<fieldset class="peek peek-noborder black" style="margin-bottom:0;">
	<legend>Always apply these filters to the worklist:</legend>
	<div id="viewCustom{if $is_custom}Req{/if}Filters{$view->id}" style="margin-top:5px;">
	{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl" is_custom=true}
	</div>
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPost('customize{$view->id}','view{$view->id}','c=internal&a=viewSaveCustomize');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<button type="button" onclick="toggleDiv('customize{$view->id}','none');"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>

</div>

<script type="text/javascript">
$(function() {
	$('#customize{$view->id}').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
});
</script>