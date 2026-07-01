{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Interactions</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Default bot name:</label>
			<input type="text" name="params[bot_name]" value="{$params.bot_name}" placeholder="e.g. &quot;Cerb&quot;">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Use this behavior to respond to new interactions:</label>
			<div class="cerb-ui-record-chooser cerb-record-chooser-behavior">
				{if $params.interaction_behavior_id}
					{$behavior = DAO_TriggerEvent::get($params.interaction_behavior_id)}
					{if $behavior}
					<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
					{/if}
				{/if}
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Portal Home Page</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Page title:</label>
			<input type="text" name="params[page_title]" value="{$params.page_title|default:''}" placeholder="Get help from our friendly chat bot">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Display the floating bot icon:</label>
			<div>
				<input type="hidden" name="params[page_hide_icon]" id="pageHideIcon_{$form_id}" value="{if $params.page_hide_icon}1{else}0{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="pageHideIcon_{$form_id}">
					<button type="button" data-value="0"{if !$params.page_hide_icon} class="cerb-ui-switcher--active"{/if}>{'common.yes'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1"{if $params.page_hide_icon} class="cerb-ui-switcher--active"{/if}>{'common.no'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Custom CSS:</label>
			<textarea name="params[page_css]" data-editor-lines="12" spellcheck="false">{$params.page_css}</textarea>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Security</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Only allow the bot widget to be embedded at this URL host:</label>
			<input type="text" name="params[cors_allow_origin]" value="{$params.cors_allow_origin|default:'*'}" placeholder="e.g. &quot;https://example.com&quot;, or * (asterisk) for any origin">
		</div>
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	$frm.find('button.save').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;

	if(window.CerbUI && CerbUI.RecordChooser)
		$frm.find('.cerb-record-chooser-behavior').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
				name: 'params[interaction_behavior_id]',
				emptyIcon: 'branch',
				query: 'event:"event.interaction.chat.portal" disabled:n'
			});
		});

	// Yes/No floating-icon toggle — Switcher bound to the hidden params[page_hide_icon] input.
	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			let input = document.getElementById(this.getAttribute('data-cerb-input'));
			if(!input) return;
			new CerbUI.Switcher(this, { value: input.value, onSelect: function(value) { input.value = value; } });
		});
	}

	// Custom CSS — a plain ScriptingEditor (no autocomplete; CSS braces are not Twig).
	let cssEl = $frm.find('textarea[name="params[page_css]"]')[0];
	if(cssEl && window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor(cssEl);
});
</script>
