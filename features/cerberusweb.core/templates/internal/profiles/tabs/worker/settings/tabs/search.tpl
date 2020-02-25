{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="search">

<fieldset class="peek">
	<legend>
		Always show these record types in the search menu: (<a href="javascript:;" onclick="checkAll('prefsSearchFavorites');">{'common.all'|devblocks_translate|lower}</a>)
	</legend>
	
	<div id="prefsSearchFavorites" style="column-width:225px;column-count:auto;">
		{foreach from=$search_contexts item=search_context}
		<div style="break-inside: avoid-column;page-break-inside: avoid;">
			<label>
				<input type="checkbox" name="search_favorites[]" value="{$search_context->id}" {if array_key_exists($search_context->id, $search_favorites)}checked="checked"{/if}> 
				{$search_context->name}
			</label>
		</div>
		{/foreach}
	</div>
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>