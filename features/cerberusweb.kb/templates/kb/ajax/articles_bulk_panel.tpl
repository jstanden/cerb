<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="doArticlesBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
	<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
</fieldset>

<fieldset>
	<legend>Add/Remove Categories</legend>
	<div style="overflow:auto;height:150px;width:98%;">
		{foreach from=$levels item=depth key=node_id}
			<input type="hidden" name="category_ids[]" value="{$node_id}">
			<select name="category_ids_{$node_id}" onchange="div=document.getElementById('kbCat{$node_id}');{literal}if('+'==selectValue(this)){div.style.color='green';div.style.background='rgb(230,230,230)';}else if('-'==selectValue(this)){div.style.color='red';div.style.background='rgb(230,230,230)';}else{div.style.color='';div.style.background='rgb(255,255,255)';}{/literal}">
				<option value=""></option>
				<option value="+">+</option>
				<option value="-">-</option>
			</select>
			<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="cerb-sprite sprite-tree_cap"></span>{else}<span class="cerb-sprite sprite-folder"></span>{/if} <span id="kbCat{$node_id}" {if (empty($article) && $root_id==$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
			<br>
		{/foreach}
	</div>
</fieldset>

{*include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=true*}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize|escape:'quotes'}");
	} );
</script>
