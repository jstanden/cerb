{$peek_context = CerberusContexts::CONTEXT_BOT}
{$peek_context_id = $model->id}
{$form_id = "frm{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="bot">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</div>
		</div>
	</div>
</div>
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#bot-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#bot-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="bot-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="bot-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				{if $model->id}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
					<div>
						{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
					</div>
				</div>
				{/if}

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
					<div>
						<input type="hidden" name="is_disabled" id="isDisabled_{$form_id}" value="{if !empty($model->is_disabled)}1{else}0{/if}">
						<div class="cerb-ui-switcher" data-cerb-input="isDisabled_{$form_id}">
							<button type="button" data-value="0"{if empty($model->is_disabled)} class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="1"{if !empty($model->is_disabled)} class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
						</div>
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.image'|devblocks_translate|capitalize}</label>
					<div>
						<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
							data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$model->id}" data-name="avatar_image"
							data-avatar="{$model->name}" data-avatar-seed="bot:{$model->id}"
							data-avatar-image="{devblocks_url}c=avatars&context=bot&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}"></span>
						<input type="hidden" name="avatar_image" value="">
					</div>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			</div>
		</div>
		{/if}
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_BOT context_id=$model->id}
		
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">{'common.configuration'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">(JSON)</small></div>
			</div>

			<textarea id="botConfigJsonEditor_{$form_id}" name="config_json" data-editor-lines="15" spellcheck="false">{$model->params.config|json_encode|devblocks_prettyjson}</textarea>
			<div class="cerb-u-text-muted" style="margin-top:5px;">(these values will be available to every behavior on this bot)</div>
		</div>

		<div class="cerb-ui-panel cerb-ui-panel--spaced va-fieldset-interactions">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Interactions</div>
			</div>

			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'worker.at_mention_name'|devblocks_translate|capitalize}</label>
					<input type="text" name="at_mention_name" value="{$model->at_mention_name}" spellcheck="false" placeholder="mybot">
				</div>
			</div>
		</div>
		
		<div class="cerb-ui-panel cerb-ui-panel--spaced va-fieldset-events">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Events</div>
			</div>

			<input type="hidden" name="allowed_events" id="allowedEvents_{$form_id}" value="{if $model->params.events.mode == 'allow'}allow{elseif $model->params.events.mode == 'deny'}deny{else}all{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="allowedEvents_{$form_id}">
				<button type="button" data-value="all"{if !$model->params.events.mode || $model->params.events.mode == 'all'} class="cerb-ui-switcher--active"{/if}>Allow all</button>
				<button type="button" data-value="allow"{if $model->params.events.mode == 'allow'} class="cerb-ui-switcher--active"{/if}>Allow only these</button>
				<button type="button" data-value="deny"{if $model->params.events.mode == 'deny'} class="cerb-ui-switcher--active"{/if}>Deny only these</button>
			</div>

			<div style="margin:5px 0px 0px 10px;{if !in_array($model->params.events.mode,['allow','deny'])}display:none;{/if}" class="va-events">
				{foreach from=$event_extensions item=event_ext key=event_ext_id}
					<label style="{if !isset($event_ext->params['contexts'][0][$model->owner_context])}display:none;{/if}" contexts="{if isset($event_ext->params['contexts'][0])}{implode(' ',$event_ext->params['contexts'][0]|array_keys)}{/if}"><input type="checkbox" name="itemized_events[]" value="{$event_ext_id}" {if is_array($model->params.events.items) && in_array($event_ext_id, $model->params.events.items)}checked="checked"{/if}> {$event_ext->name}<br></label>
				{/foreach}
			</div>
		</div>
		
		<div class="cerb-ui-panel cerb-ui-panel--spaced va-fieldset-actions">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Action Extensions</div>
			</div>

			<input type="hidden" name="allowed_actions" id="allowedActions_{$form_id}" value="{if $model->params.actions.mode == 'allow'}allow{elseif $model->params.actions.mode == 'deny'}deny{else}all{/if}">
			<div class="cerb-ui-switcher" data-cerb-input="allowedActions_{$form_id}">
				<button type="button" data-value="all"{if !$model->params.actions.mode || $model->params.actions.mode == 'all'} class="cerb-ui-switcher--active"{/if}>Allow all</button>
				<button type="button" data-value="allow"{if $model->params.actions.mode == 'allow'} class="cerb-ui-switcher--active"{/if}>Allow only these</button>
				<button type="button" data-value="deny"{if $model->params.actions.mode == 'deny'} class="cerb-ui-switcher--active"{/if}>Deny only these</button>
			</div>

			<div style="margin:5px 0px 0px 10px;{if empty($model->params.actions.mode) || $model->params.actions.mode == 'all'}display:none;{/if}" class="va-actions">
				{foreach from=$action_extensions item=action_ext key=action_ext_id}
					<label events="{if isset($action_ext->params['events'][0])}{implode(' ', $action_ext->params['events'][0]|array_keys)}{/if}"><input type="checkbox" name="itemized_actions[]" value="{$action_ext->id}" {if is_array($model->params.actions.items) && in_array($action_ext_id, $model->params.actions.items)}checked="checked"{/if}> {$action_ext->params.label}<br></label>
				{/foreach}
			</div>
		</div>
		
		{if !empty($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="bot and all of its behaviors"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle export"><span class="cerb-icons cerb-icon-file-export"></span> {'common.export'|devblocks_translate|capitalize}</button>{/if}
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bot'|devblocks_translate|capitalize|escape:'javascript'}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
		$popup.find('button.export').click(function() {
			genericAjaxPopup('export_bot', 'c=profiles&a=invoke&module=bot&action=showExportBotPopup&id={$model->id}',null,false,'50%');
		});

		
		// Package Library
		
		{if !$model->id}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			
			{if $packages}
				var $library_container = $tabs;
				{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
				
				$library_container.on('cerb-package-library-form-submit', function(e) {
					$popup.one('peek_saved peek_error', function(e) {
						$library_container.triggerHandler('cerb-package-library-form-submit--done');
					});
					
					$popup.find('button.save').click();
				});
			{/if}
		{/if}

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Switchers (status + the Events/Actions allow-mode). The two allow-mode switchers toggle their
		// itemized-checkbox list (shown only for allow/deny); dispatched by input name in onSelect.
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				var input = document.getElementById(this.getAttribute('data-cerb-input'));
				if(!input) return;
				new CerbUI.Switcher(this, {
					value: input.value,
					onSelect: function(value) {
						input.value = value;
						if(input.name === 'allowed_events')
							$popup.find('.va-events')[value === 'all' ? 'hide' : 'show']();
						if(input.name === 'allowed_actions')
							$popup.find('.va-actions')[value === 'all' ? 'hide' : 'show']();
					}
				});
			});
		}

		// Editor
		new CerbUI.JsonEditor($popup.find('#botConfigJsonEditor_{$form_id}')[0], { validate: true, minLines: 4 });

		// Avatar

		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });
		
	});
});
</script>
