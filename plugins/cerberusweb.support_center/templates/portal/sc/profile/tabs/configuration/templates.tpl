{if $templates_enabled}
	<div class="error-box">
		<h1>Deprecated</h1>
		<p>Custom templates in portals are deprecated and will be removed in future version.</p>
	</div>
{else}
	<div class="error-box">
		<h1>Disabled</h1>
		<p>Custom templates in portals are disabled and will be removed in future version. Use <code>APP_OPT_DEPRECATED_PORTAL_CUSTOM_TEMPLATES</code> to temporarily re-enable.</p>
	</div>
{/if}

<form action="#" style="margin-bottom:5px;float:left;">
	<button type="button" data-cerb-button="template_add"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button></a>
	<button type="button" data-cerb-button="template_import"><span class="glyphicons glyphicons-file-import"></span> {'common.import'|devblocks_translate|capitalize}</button></a>
</form>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');
	let $frm = $script.prev('form');

	$frm.find('[data-cerb-button=template_add]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=profiles&a=invoke&module=community_portal&action=showConfigTab&config_tab=templates&tab_action=showAddTemplatePeek&portal_id={$portal->id}&view_id={$view->id|escape:'url'}',null,false,'80%');
	});

	$frm.find('[data-cerb-button=template_import]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('import','c=internal&a=invoke&module=portals&action=showImportTemplatesPeek&portal_id={$portal->id}&view_id={$view->id|escape:'url'}',null,false,'50%');
	});
});
</script>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}