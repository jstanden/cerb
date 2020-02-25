{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="pages">

<fieldset class="peek">
	<legend>Show these pages in the navigation bar:</legend>
	
	<div style="margin-left:10px;"></div>
	
	<button type="button" class="chooser-abstract" data-field-name="pages[]" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{foreach from=$pages item=page}
		<li style="cursor:move;"><input type="hidden" name="pages[]" value="{$page->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-context-id="{$page->id}">{$page->name}</a></li>
		{/foreach}
	</ul>
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');

	$frm.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$frm.find('ul.bubbles')
		.sortable({
			items: 'li',
			placeholder:'ui-state-highlight'
		})
		;
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>