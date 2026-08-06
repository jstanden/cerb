{$peek_context = CerberusContexts::CONTEXT_BEHAVIOR}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form id="frmDecisionBehavior{$model->id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="id" value="{$model->id|default:0}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
	{if $bot}
	<input type="hidden" name="bot_id" value="{$bot->id}">
	{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.bot'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser cerb-record-chooser-bot"></div>
			</div>
		</div>
	</div>
	{/if}
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#behavior-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#behavior-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#behavior-import_{$form_id}">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="behavior-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$model->id}
	<div id="behavior-import_{$form_id}">
		<textarea name="import_json" style="width:100%;height:250px;box-sizing:border-box;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a behavior in JSON format"></textarea>
		
		<div class="config"></div>
		
		<div>
			<button type="button" class="import"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}
	
	<div id="behavior-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{if $model->id}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.bot'|devblocks_translate|capitalize}</label>
					{if $bot}
						<ul class="bubbles chooser-container">
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}"><input type="hidden" name="bot_id" value="{$bot->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$bot->id}">{$bot->name}</a></li>
						</ul>
					{/if}
				</div>
				{/if}

				<div class="cerb-ui-form--field behavior-event">
					<label class="cerb-ui-form--label">{'common.event'|devblocks_translate|capitalize}</label>
					{if $ext}
						<ul class="bubbles">
							<li>{$ext->manifest->name}</li>
						</ul>
					{else}
					<div class="events-widget" style="display:none;">
						{if $events_menu}
							{include file="devblocks:cerb.behaviors.legacy::internal/peek/menu_behavior_event.tpl"}
						{else}
							(choose a bot to see available events)
						{/if}
					</div>
					{/if}

					<div class="event-params">
					{if $ext && method_exists($ext,'renderEventParams')}
					{$ext->renderEventParams($model)}
					{/if}
					</div>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="title" value="{$model->title}" autocomplete="off" spellcheck="false" autofocus="autofocus">
				</div>

				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field cerb-u-flex-2">
						<label class="cerb-ui-form--label"><abbr title="An optional unique alias for this behavior. Lowercase letters, numbers, and underscores.">{'common.uri'|devblocks_translate|capitalize}</abbr></label>
						<input type="text" name="uri" value="{$model->uri}" placeholder="(optional)" spellcheck="false">
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.priority'|devblocks_translate|capitalize}</label>
						<input type="text" name="priority" value="{$model->priority|default:50}" placeholder="50" maxlength="2" autocomplete="off" spellcheck="false">
					</div>
				</div>

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

				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
				{/if}
			</div>
		</div>
		
		<div class="cerb-ui-panel cerb-ui-panel--spaced behavior-variables">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">{'common.variables'|devblocks_translate|capitalize}</div>
			</div>

			<div id="divBehaviorVariables{$model->id}">
			{foreach from=$model->variables key=k item=var name=vars}
				{$seq = uniqid()}
				{include file="devblocks:cerb.behaviors.legacy::internal/decisions/editors/trigger_variable.tpl" seq=$seq}
			{/foreach}
			</div>
			
			<div style="margin:5px 0px 10px 20px;">
				<button type="button" class="add-variable cerb-popupmenu-trigger">{'common.add'|devblocks_translate|capitalize} &#x25be;</button>
				
				{function menu level=0}
					{foreach from=$keys item=data key=idx}
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
								{if $data->key}
									<div style="font-weight:bold;">{$data->l|capitalize}</div>
								{else}
									<div>{$idx|capitalize}</div>
								{/if}
								<ul style="width:200px;">
									{menu keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
							{$item_context = explode(':', $data->key)}
							<li data-token="{$data->key}" data-label="{$data->label}">
								<div style="font-weight:bold;">
									{$data->l|capitalize}
								</div>
							</li>
						{/if}
					{/foreach}
				{/function}
				
				<ul class="chooser-container bubbles"></ul>
				
				<ul class="add-variable-menu" style="width:150px;{if $model && $model->event_point}display:none;{/if}">
				{menu keys=$variables_menu}
				</ul>
			</div>
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_BEHAVIOR context_id=$model->id}

		{if isset($model->id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="behavior and all of its effects"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmDecisionBehavior{$model->id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.behavior'|devblocks_translate|capitalize|escape:'javascript'}");
		$popup.css('overflow', 'inherit');
		
		let loadEventsByBot = function(bot_id) {
			// Load the events menu for the chosen bot
			if(bot_id) {
				genericAjaxGet('', 'c=profiles&a=invoke&module=behavior&action=getEventsMenuByBot&bot_id=' + bot_id, function(html) {
					$popup.find('div.events-widget').html(html).fadeIn();
					$popup.trigger('events-menu-refresh');
				});
			} else {
				$popup.find('div.events-widget').hide();
				$frm.find('div.event-params').hide();
			}
		};

		if(window.CerbUI && CerbUI.RecordChooser)
			$popup.find('.cerb-record-chooser-bot').each(function() {
				new CerbUI.RecordChooser(this, {
					context: '{CerberusContexts::CONTEXT_BOT}',
					name: 'bot_id',
					emptyIcon: 'bot',
					onSelect: function(item) { loadEventsByBot(item.id); }
				});
			});
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		var checkForConfigForm = function(json) {
			if(json.config_html) {
				//$frm.find('div.import').hide();
				$frm.find('div.config').hide().html(json.config_html).fadeIn();
			}
		};
		
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);
		$popup.find('button.import').click({ after: checkForConfigForm }, Devblocks.callbackPeekEditSave);

		// Status switcher (enabled/disabled) bound to the hidden is_disabled input
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				var input = document.getElementById(this.getAttribute('data-cerb-input'));
				if(input) new CerbUI.Switcher(this, { value: input.value, onSelect: function(v) { input.value = v; } });
			});
		}
		
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

		// Behavior variables — each row is a .cerb-behavior-var panel, dragged by its .cerb-ui-header
		if(window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable($popup.find('#divBehaviorVariables{$model->id}').get(0), { items:'.cerb-behavior-var', handle:'.cerb-ui-header' });

		$popup.find('.behavior-variables').on({
			mouseenter: function() {
				$(this).find(':hidden[data-cerb-onhover]').show();
			},
			mouseleave: function() {
				$(this).find(':visible[data-cerb-onhover]').hide();
			}
		}, ".cerb-behavior-var");

		var $variables = $('#divBehaviorVariables{$model->id}');
		
		new CerbUI.Menu($popup.find('ul.add-variable-menu').hide()[0], {
			clickTrigger: $popup.find('BUTTON.add-variable')[0],
			filter: true,
			onSelect: function(li, src) {
				var field_type = src.getAttribute('data-token');

				if(null != field_type) {
					genericAjaxGet('', 'c=profiles&a=invoke&module=behavior&action=getTriggerVariableParams&type=' +  encodeURIComponent(field_type), function(o) {
						var $html = $(o).appendTo($variables);
					});
				}
			}
		});
		
		// Events

		let initEventsMenu = function() {
			let $widget = $popup.find('div.events-widget');
			let menuEl = $widget.find('ul.events-menu')[0];

			if(!menuEl || !(window.CerbUI && CerbUI.Menu))
				return;

			let $events_ul = $widget.find('ul.chooser-container');
			let $trigger = $widget.find('button.events-menu-trigger');

			// Nested events tree; branches only expand. Type-to-filter searches all leaf events.
			new CerbUI.Menu($(menuEl).hide()[0], {
				clickTrigger: $trigger[0],
				filter: true,
				onSelect: function(li, src) {
					let token = src.getAttribute('data-token');
					let label = src.getAttribute('data-label');

					if(!token || !label)
						return;

					// Build the selected-event bubble
					let $li = $('<li/>');
					$('<span/>').attr('data-event', token).text(label).appendTo($li);
					$('<input type="hidden">').attr('name', 'event_point').attr('value', token).appendTo($li);
					$('<a><span class="cerb-icons cerb-icon-circle-remove"></span></a>')
						.appendTo($li)
						.on('click', function(e) {
							e.stopPropagation();
							$(this).trigger('events-bubble-remove');
						})
					;

					$events_ul.find('> *').remove();
					$events_ul.append($li).show();
					$trigger.hide();

					genericAjaxGet('', 'c=profiles&a=invoke&module=behavior&action=getTriggerEventParams&id=' + encodeURIComponent(token), function(o) {
						$frm.find('div.event-params').html(o).fadeIn();
					});
				}
			});
		};

		$popup.on('events-bubble-remove', function(e) {
			let $widget = $popup.find('div.events-widget');

			e.stopPropagation();
			$(e.target).closest('li').remove();
			$widget.find('ul.chooser-container').hide();
			$widget.find('button.events-menu-trigger').show();
			$frm.find('div.event-params').hide();
		});

		$popup.on('events-menu-refresh', function(e) {
			initEventsMenu();
		});
		
		{if $events_menu}
		$popup.trigger('events-menu-refresh');
		$popup.find('div.events-widget').fadeIn();
		{/if}
		
	});
});
</script>