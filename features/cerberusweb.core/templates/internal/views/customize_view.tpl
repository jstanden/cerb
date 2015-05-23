<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewSaveCustomize">
<input type="hidden" name="id" value="{$view->id}">
<div class="block" style="margin:5px;">
<h1>{'common.customize'|devblocks_translate|capitalize}</h1>

{* Custom Views *}
{if substr($view->id,0,5)=="cust_"}
	{assign var=is_custom value=true}
{else}
	{assign var=is_custom value=false}
{/if}

{* Trigger Views *}
{if substr($view->id,0,9)=="_trigger_"}
	{assign var=is_trigger value=true}
{else}
	{assign var=is_trigger value=false}
{/if}

{if $is_custom || $is_trigger}
<b>List Title:</b><br>
<input type="text" name="title" value="{$view->name}" size="64" autocomplete="off"><br>
<br>
{/if}

<b>{'dashboard.columns'|devblocks_translate|capitalize}:</b> 
 &nbsp; 
<a href="javascript:;" onclick="Devblocks.resetSelectElements('customize{$view->id}','columns[]');">{'common.clear'|devblocks_translate|lower}</a>
<br>

{foreach from=$columns item=column}
<div class="column"> 
<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>
<label><input type="checkbox" name="columns[]" value="{$column->token}" {if in_array($column->token, $view->view_columns)}checked="checked"{/if}> {$column->db_label|capitalize}</label>
</div>
{/foreach}
<br>

<b>{'dashboard.num_rows'|devblocks_translate|capitalize}:</b> <input type="text" name="num_rows" size="3" maxlength="3" value="{$view->renderLimit}"><br>
<br>

{if $is_custom}
<b>Always apply these filters to this worklist:</b><br>
<div id="viewCustom{if $is_custom}Req{/if}Filters{$view->id}" style="margin:10px;">
{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl" is_custom=true}
</div>
<br>
{/if}

<button type="button" onclick="genericAjaxPost('customize{$view->id}','view{$view->id}','c=internal&a=viewSaveCustomize');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<button type="button" onclick="toggleDiv('customize{$view->id}','none');"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>

<br>
<br>
</div>

<script type="text/javascript">
	$('#customize{$view->id}').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
</script>