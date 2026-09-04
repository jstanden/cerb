{$uniqid = uniqid('formBuilder')}

{* Load any preview stylesheets the trigger supplies (e.g. interaction.website's portal CSS) so components render
   with their real front-end styling. Deduped by href so reopening the popup doesn't stack <link>s. *}
{foreach from=$preview_stylesheets|default:[] item=sheet}
	<link rel="stylesheet" data-cerb-form-preview-css href="{devblocks_url}c=resource&p={$sheet.p}&f={$sheet.f}{/devblocks_url}">
{/foreach}

<div id="{$uniqid}" class="cerb-fb-popup"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $div = $('#{$uniqid}');
	const $popup = genericAjaxPopupFind($div);

	const extensionId = '{$extension_id|escape:'javascript'}';
	const components = {$form_components_json nofilter};
	const schema = {$form_schema_json nofilter};
	const recordTypes = {$record_types_json nofilter};
	const mapResources = {$map_resources_json nofilter};
	const agentModels = {$agent_models_json nofilter};
	const accountUris = {$account_uris_json nofilter};

	$popup.one('popup_open', function() {
		$popup.dialog('option', 'title', 'Form builder');

		{* sheet-builder.js rides along because the "Design sheet" button is only rendered when
		   CerbUI.SheetBuilder is ALREADY defined -- loading it later drops the button with no error. *}
		Devblocks.loadResources({
			'js': [
				'/resource/cerberusweb.core/js/cerb-ui/form-builder.js?v={$smarty.const.APP_BUILD}',
				'/resource/cerberusweb.core/js/cerb-ui/sheet-builder.js?v={$smarty.const.APP_BUILD}'
			]
		}, function() {
			if(!(window.CerbUI && CerbUI.FormBuilder)) {
				console.error('CerbUI.FormBuilder failed to load');
				return;
			}

			new CerbUI.FormBuilder($div[0], {
				extensionId: extensionId,
				components: components,
				schema: schema,
				recordTypes: recordTypes,
				mapResources: mapResources,
				agentModels: agentModels,
				accountUris: accountUris,
				previewChrome: '{$preview_chrome|default:'dialog'|escape:'javascript'}'
			});
		});
	});
});
</script>
