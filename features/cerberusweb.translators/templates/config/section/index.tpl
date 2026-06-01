<div>
	<h2>{'translators.common'|devblocks_translate|capitalize}</h2>
</div>

<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

{$form_id = uniqid('form')}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;" method="post">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	<button type="button" data-cerb-button-add><span class="cerb-icons cerb-icon-globe"></span> {'translators.languages'|devblocks_translate|capitalize}</button>
	<button type="button" data-cerb-button-sync><span class="cerb-icons cerb-icon-refresh"></span> {'common.synchronize'|devblocks_translate|capitalize}</button>
	<button type="button" data-cerb-button-import><span class="cerb-icons cerb-icon-file-import"></span> {'common.import'|devblocks_translate|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	$frm.find('[data-cerb-button-add]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=translations&action=showAddLanguagePanel',null,false,'50%');
	});

	$frm.find('[data-cerb-button-sync]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=translations&action=showFindStringsPanel',null,false,'50%');
	});

	$frm.find('[data-cerb-button-import]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('peek','c=config&a=invoke&module=translations&action=showImportStringsPanel',null,false,'50%');
	});
});
</script>