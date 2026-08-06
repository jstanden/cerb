{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="search">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Always show these record types in the search menu</div>
		<div class="cerb-ui-header--right"><a data-cerb-link="check_all" class="cerb-u-cursor-pointer">{'common.all'|devblocks_translate|lower}</a></div>
	</div>

	<div id="prefsSearchFavorites" style="column-width:225px;column-count:auto;">
		{foreach from=$search_contexts item=search_context}
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mb-1" style="break-inside:avoid-column;page-break-inside:avoid;">
			<label class="cerb-ui-toggle"><input type="checkbox" name="search_favorites[]" id="sf_{$search_context->id}_{$form_id}" value="{$search_context->id}" {if array_key_exists($search_context->id, $search_favorites)}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="sf_{$search_context->id}_{$form_id}" class="cerb-u-flex cerb-u-items-center cerb-u-gap-1"><span class="cerb-icons cerb-icon-{$search_context->params.icon|default:'collection'} cerb-u-text-muted"></span> {$search_context->name}</label>
		</div>
		{/foreach}
	</div>
</div>

<div>
	<div class="cerb-ui-toolbar-strip">
		<button type="button" id="btnSave_{$form_id}" class="cerb-ui-toolbar-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$frm.find('[data-cerb-link=check_all]').on('click', function(e) {
		e.stopPropagation();
		checkAll('prefsSearchFavorites');
	});

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
