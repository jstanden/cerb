{$peek_context = CerberusContexts::CONTEXT_AUTOMATION}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if $model}{$extension = $model->getTriggerExtension()}{else}{$extension = null}{/if}

<div id="automationAgentMount{$form_id}">
<form action="{devblocks_url}{/devblocks_url}" method="post" id="editor{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="automation">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="is_simulator" value="1">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="automation"}

{$has_templates = (!$model->id && !empty($automation_templates))}
{$template_count = 0}
{if $has_templates}{foreach $automation_templates as $g}{$template_count = $template_count + ($g.templates|count)}{/foreach}{/if}

{if $has_templates}
{* Automation Builder: a new automation opens on this template picker; the editor form below stays hidden
   until a template is applied or the author skips. Picking a candidate sets its trigger + seeds the editor
   (ephemerally — nothing is saved until Save). *}
<style>{literal}
[data-cerb-automation-template-picker] .cerb-ab-template-search { width:100%; margin-bottom:0.75em; }
[data-cerb-automation-template-picker] .cerb-ab-template-group { margin-bottom:1em; }
[data-cerb-automation-template-picker] .cerb-ab-template-group-label { margin-bottom:0.4em; }
[data-cerb-automation-template-picker] .cerb-ab-template-cards { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:0.6em; }
[data-cerb-automation-template-picker] .cerb-ab-template-card { display:flex; align-items:flex-start; gap:0.6em; text-align:left; padding:0.75em; border:1px solid var(--cerb-color-background-contrast-200); border-radius:6px; background:var(--cerb-color-background); cursor:pointer; }
[data-cerb-automation-template-picker] .cerb-ab-template-card:hover, [data-cerb-automation-template-picker] .cerb-ab-template-card:focus { border-color:var(--cerb-color-primary, #3a7bd5); outline:none; }
[data-cerb-automation-template-picker] .cerb-ab-template-card-icon { flex:0 0 auto; margin-top:0.15em; }
[data-cerb-automation-template-picker] .cerb-ab-template-card-body { display:flex; flex-direction:column; gap:0.2em; min-width:0; }
[data-cerb-automation-template-picker] .cerb-ab-template-apply-strip { margin-top:0.75em; }
[data-cerb-automation-template-picker] .cerb-ab-template-wizard-panel { position:relative; }
[data-cerb-automation-template-picker] .cerb-ab-template-back-bar { position:absolute; top:0; right:0; z-index:1; }
{/literal}</style>
<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-automation-template-picker>
	<div data-cerb-template-browse>
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Start from a template</div>
			<div class="cerb-ui-header--right">
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-template-skip>Start from scratch <span class="cerb-icons cerb-icon-circle-arrow-right"></span></button>
			</div>
		</div>

		{* Only bother with a filter box once there are enough templates to warrant one. *}
		{if $template_count >= 8}
		<input type="text" class="cerb-ab-template-search" placeholder="Filter templates&hellip;" spellcheck="false" autofocus="autofocus">
		{/if}

		<div data-cerb-template-list>
			{foreach from=$automation_templates item=group}
			<div class="cerb-ab-template-group" data-cerb-template-group>
				<div class="cerb-ab-template-group-label cerb-u-text-uppercase cerb-u-text-muted cerb-u-fs-n1">{$group.section}</div>
				<div class="cerb-ab-template-cards">
					{foreach from=$group.templates item=t}
					<div class="cerb-ab-template-card" role="button" tabindex="0" data-cerb-template-card
						data-template-id="{$t.id}" data-has-wizard="{if $t.has_wizard}1{else}0{/if}"
						data-search="{$t.label|lower} {$t.description|lower} {$group.section|lower}">
						<span class="cerb-icons cerb-icon-{$t.icon} cerb-ab-template-card-icon"></span>
						<span class="cerb-ab-template-card-body">
							<b>{$t.label}</b>
							<span class="cerb-u-text-muted">{$t.description}</span>
						</span>
					</div>
					{/foreach}
				</div>
			</div>
			{/foreach}
		</div>
	</div>

	{* Slide-in config for a picked candidate that declares a wizard (server-rendered CerbUI). Back stays
	   top-left inside the panel; the "Use this template" action lives in its own toolbar strip BELOW the panel
	   (shown only while a wizard is open) so it reads as the obvious next step. *}
	<div data-cerb-template-wizard hidden class="cerb-ab-template-wizard-panel">
		{* Back sits at the top-right, on the same row as each wizard's own title, to save a row. *}
		<div class="cerb-ab-template-back-bar">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-template-back><span class="cerb-icons cerb-icon-chevron-left"></span> {'common.back'|devblocks_translate|capitalize}</button>
		</div>
		<div data-cerb-template-wizard-body></div>
	</div>

	<div class="cerb-ui-toolbar-strip cerb-ab-template-apply-strip" data-cerb-template-apply-strip hidden>
		<button type="button" class="cerb-ui-toolbar-button" data-cerb-template-apply><span class="cerb-icons cerb-icon-circle-ok"></span> Use this template</button>
	</div>
</div>
{/if}

<div data-cerb-automation-editor-body{if $has_templates} hidden{/if}>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}"{if !$has_templates} autofocus="autofocus"{/if} spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
			<input type="text" name="description" value="{$model->description}">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.trigger'|devblocks_translate|capitalize}</label>
			<div>
				<button type="button" data-cerb-trigger-chooser data-interaction-uri="ai.cerb.cardEditor.automation.triggerChooser" data-interaction-params=""><span class="cerb-icons cerb-icon-search"></span></button>
				<ul class="chooser-container bubbles" style="display:inline-block;">
					{if $extension}
					<li>
						{$extension->manifest->id}
						<input type="hidden" name="extension_id" value="{$extension->id}">
						<span class="cerb-icons cerb-icon-circle-remove"></span>
					</li>
					{/if}
				</ul>

				<div data-cerb-extension-params>
					{if $extension}
						{$extension->renderConfig($model)}
					{/if}
				</div>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

<div data-cerb-automation-editor-script>
	<div data-cerb-automation-script-toolbar class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		{* Static editor actions; the per-trigger interaction items are spliced in at the front (before the first
		   static item — an empty <li> that also serves as the divider) on init + whenever the trigger changes. *}
		<ul class="cerb-ui-toolbar" id="script_toolbar_{$form_id}">
			<li data-static></li>
			{if $model->id}
				<li data-static data-key="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
			{/if}
			{* Toggled client-side on trigger change (renderEditorToolbar flags form-builder-capable triggers). *}
			<li data-static data-key="formbuilder" data-icon="form" title="Form builder"{if !($extension && method_exists($extension, 'getFormComponentMeta') && method_exists($extension, 'getFormComponentSchema'))} hidden{/if}></li>
			<li data-static data-key="export" data-icon="upload" title="{'common.export'|devblocks_translate|capitalize}"></li>
			<li data-static data-key="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
		</ul>

		{* Initial per-trigger interaction items (their own ul); the JS moves these <li>s into the strip above. *}
		<div data-cerb-toolbar-dynamic-source hidden>
			{if is_a($extension, 'Extension_AutomationTrigger')}
				{$toolbar_dict = DevblocksDictionaryDelegate::instance([
					'caller_name' => 'cerb.toolbar.editor.automation.script',

					'worker__context' => CerberusContexts::CONTEXT_WORKER,
					'worker_id' => $active_worker->id
				])}
				{$toolbar = $extension->getEditorToolbar()}
				{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar, $toolbar_dict)}
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			{/if}
		</div>
	</div>
	<textarea name="automation_script" data-editor-lines="25" spellcheck="false">{$model->script}</textarea>
</div>

{$tabs_uid = uniqid('automationTabs')}
<div id="{$tabs_uid}" style="margin-top:10px;" data-cerb-automation-editor-tabs>
	<ul>
		<li data-cerb-tab="run"><a href="#{$tabs_uid}Run">{'common.run'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="policy"><a href="#{$tabs_uid}Policy">{'common.policy'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="log"><a href="#{$tabs_uid}Log">{'common.log'|devblocks_translate|capitalize}</a></li>
		<li data-cerb-tab="visualization"><a href="#{$tabs_uid}Visualization">Visualization</a></li>
		<li data-cerb-tab="usage"><a href="#{$tabs_uid}Usage">Usage</a></li>
	</ul>

	<div id="{$tabs_uid}Run">
		<div style="display:flex;gap:0.5em;">
			<div class="cerb-ui-panel" style="flex:1 1 50%;" data-cerb-automation-editor-state-start>
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">{'common.input'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">(YAML)</small></div>
				</div>

				<div class="cerb-ui-toolbar-strip">
					<button type="button" title="Simulate" class="cerb-ui-toolbar-button cerb-editor-toolbar-button--mode" data-mode="simulator">Simulate</button>
					<span class="cerb-ui-toolbar--divider"></span>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--preview-form" title="Open form" style="display:none;"><span class="cerb-icons cerb-icon-form cerb-u-anim-magic-sweep"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--clear" title="Clear" style="display:none;"><span class="cerb-icons cerb-icon-erase"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--interaction" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=input" title="{'common.help'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-question-mark"></span></button>
				</div>

				<textarea name="start_state_yaml" spellcheck="false"></textarea>
			</div>

			<div class="cerb-ui-panel" style="flex:1 1 50%;" data-cerb-automation-editor-state-end>
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">{'common.output'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">(YAML)</small></div>
				</div>

				<div class="cerb-ui-toolbar-strip">
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--step" title="Copy to input" style="display:none;"><span class="cerb-icons cerb-icon-chevron-left cerb-u-anim-magic-sweep"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--diff" title="Compare to input" style="display:none;"><span class="cerb-icons cerb-icon-split-pane"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--clear" title="Clear" style="display:none;"><span class="cerb-icons cerb-icon-erase"></span></button>
					<button type="button" class="cerb-ui-toolbar-button cerb-code-editor-toolbar-button--interaction" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=output" title="{'common.help'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-question-mark"></span></button>
				</div>

				<textarea name="end_state_yaml" spellcheck="false"></textarea>
			</div>
		</div>
	</div>

	<div id="{$tabs_uid}Policy">
		<div>
			This policy determines which actions this automation is allowed to perform.
		</div>
		{* The editor toolbar is the KataEditor's integrated strip below; this hidden <ul> is its host section
		   (Suggest + Change history via onAction; Help fires as an interaction). *}
		<ul class="cerb-ui-toolbar" data-cerb-policy-toolbar hidden>
			<li data-value="generate" data-icon="sparkles" title="Generate least-privilege policy from the script"></li>
			<li></li>
			<li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
			{if $model->id}
				<li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
			{/if}
			<li data-icon="circle-question-mark" data-interaction-uri="ai.cerb.automationBuilder.help" data-interaction-params="topic=policy" title="{'common.help'|devblocks_translate|capitalize}"></li>
		</ul>

		<textarea name="automation_policy_kata" data-editor-lines="25" spellcheck="false">{$model->policy_kata}</textarea>
	</div>

	<div id="{$tabs_uid}Log"></div>
	<div id="{$tabs_uid}Visualization"></div>
	<div id="{$tabs_uid}Usage"></div>
</div>

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="automation"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</div>{* /data-cerb-automation-editor-body *}
</form>
</div>{* #automationAgentMount — AgentPane wraps this *}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#editor{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$frm.find('[data-cerb-automation-editor-tabs] > ul').each(function() {
			if(!(window.CerbUI && CerbUI.Tabs)) return;
			new CerbUI.Tabs(this, { onTabSelected: function(index, tab) {
				var $panel = $(tab.panel);
				var formData;

				if(tab.li.getAttribute('data-cerb-tab') === 'visualization') {
					Devblocks.getSpinner().appendTo($panel.html(''));

					formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation');
					formData.set('action', 'editorVisualize');
					formData.set('script', editor_automation.getValue());
					formData.set('extension_id', $frm.find('input:hidden[name=extension_id]').val() || '');

					genericAjaxPost(formData, null, null, function (html) {
						$panel.html(html);
					});

				} else if(tab.li.getAttribute('data-cerb-tab') === 'usage') {
					Devblocks.getSpinner().appendTo($panel.html(''));

					let extension_id = $frm.find('input:hidden[name=extension_id]').val();

					if(!extension_id) {
						$panel.text('(no usage found)');
						return;
					}

					formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation');
					formData.set('action', 'editorUsage');
					formData.set('automation_name', $frm.find('input[name=name]').val());
					formData.set('trigger', extension_id);

					genericAjaxPost(formData, null, null, function (html) {
						$panel.html(html);
						// Usage tiles are injected here (after popup_open ran), so wire their peek triggers now.
						$panel.find('.cerb-peek-trigger').cerbPeekTrigger();
					});

				} else if(tab.li.getAttribute('data-cerb-tab') === 'log') {
					Devblocks.getSpinner().appendTo($panel.html(''));

					formData = new FormData();
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'automation');
					formData.set('action', 'editorLog');
					formData.set('automation_name', $frm.find('input[name="name"]').val());

					genericAjaxPost(formData, null, null, function (html) {
						$panel.html(html);
					});
				}
			} });
		});

		var $script = $frm.find('[data-cerb-automation-editor-script]');
		var $script_toolbar_wrapper = $script.find('[data-cerb-automation-script-toolbar]');
		var $automation_yaml = $script.find('textarea[name=automation_script]');

		var $state_start = $frm.find('[data-cerb-automation-editor-state-start]');
		var $state_yaml = $state_start.find('textarea[name=start_state_yaml]');
		var $state_start_toolbar = $state_start.find('.cerb-ui-toolbar-strip');
		var $button_run = $state_start_toolbar.find('.cerb-code-editor-toolbar-button--run');
		var $toggle_mode = $state_start_toolbar.find('.cerb-editor-toolbar-button--mode');

		var $state_end = $frm.find('[data-cerb-automation-editor-state-end]');
		var $end_state_yaml = $state_end.find('textarea[name=end_state_yaml]');
		var $state_end_toolbar = $state_end.find('.cerb-ui-toolbar-strip');
		var $button_step = $state_end_toolbar.find('.cerb-code-editor-toolbar-button--step');

		var $spinner = Devblocks.getSpinner().css('max-width', '16px');

		var $extension_params = $frm.find('[data-cerb-extension-params]');

		$popup.dialog('option', 'title', "{'common.automation'|devblocks_translate|capitalize}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Help
		
		$popup.find('.cerb-code-editor-toolbar-button--interaction')
			.cerbBotTrigger()
		;
		
		// Trigger chooser

		var $trigger_chooser = $popup.find('[data-cerb-trigger-chooser]');

		// Select a trigger: render its bubble + hidden extension_id, and live-swap the editor's config, toolbar,
		// and KATA autocomplete for it. Shared by the trigger chooser (done) and the Automation Builder's apply.
		var applyTrigger = function(extension_id, extension_name) {
			var $container = $trigger_chooser.siblings('ul.chooser-container');

			var $hidden = $('<input/>')
				.attr('type', 'hidden')
				.attr('name', 'extension_id')
				.val(extension_id)
			;

			var $remove = $('<span class="cerb-icons cerb-icon-circle-remove"></span>');

			var $li = $('<li/>')
				.text(extension_name)
				.append($hidden)
				.append($remove)
			;

			$container.empty().append($li);

			// The prior trigger's run line-markers no longer apply to the (now different) scope.
			editor_automation.clearHighlight();

			$extension_params.empty().append(Devblocks.getSpinner());

			var formData;

			// Update config for trigger

			formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'getExtensionConfig');
			formData.set('extension_id', extension_id);

			genericAjaxPost(formData, $extension_params);

			// Update toolbar for editor

			formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'renderEditorToolbar');
			formData.set('trigger', extension_id);

			genericAjaxPost(formData, null, null, function(html) {
				// Re-render the per-trigger interaction items, re-splice them into the strip, and rebuild it.
				var $source = $script_toolbar_wrapper.find('[data-cerb-toolbar-dynamic-source]');
				$source.html(html);
				spliceToolbarDynamic($source.find('> ul')[0]);
				// Show/hide the Form Builder button per the server's capability flag for this trigger.
				var fb_li = toolbar_ul ? toolbar_ul.querySelector(':scope > li[data-key="formbuilder"]') : null;
				if(fb_li) fb_li.hidden = !$source.find('[data-cerb-supports-form-builder]').length;
				if(script_toolbar) script_toolbar.refresh();
			});

			// Update autocompletion for editor

			formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'getAutocompleteJson');
			formData.set('extension_id', extension_id);

			genericAjaxPost(formData, null, null, function(json) {
				// Live-swap the editor's KATA autocomplete to the chosen trigger's schema.
				editor_automation.opts.onAutocomplete = CerbUI.KataEditor.kataFieldSource(json);
			});
		};

		$trigger_chooser.siblings('.chooser-container').on('click', function(e) {
			e.stopPropagation();

			var $target = $(e.target);

			if(!$target.is('.cerb-icon-circle-remove'))
				return;

			$target.closest('li').remove();

			$extension_params.empty();

			// Drop the per-trigger interaction items from the editor toolbar; no trigger → no Form Builder.
			spliceToolbarDynamic(null);
			var fb_li = toolbar_ul ? toolbar_ul.querySelector(':scope > li[data-key="formbuilder"]') : null;
			if(fb_li) fb_li.hidden = true;
			if(script_toolbar) script_toolbar.refresh();

			// Clear the editor's KATA autocomplete (no trigger selected → no schema).
			editor_automation.opts.onAutocomplete = CerbUI.KataEditor.kataFieldSource([]);

			// The prior trigger's run line-markers no longer apply to the (now different) scope.
			editor_automation.clearHighlight();
		});

		$trigger_chooser.cerbBotTrigger({
			caller: {
				name: 'cerb.toolbar.editor.automation.trigger',
				params: {
				}
			},
			width: '75%',
			start: function(formData) {
			},
			done: function(e) {
				if('object' !== typeof e || !e.hasOwnProperty('eventData'))
					return;

				var $target = e.trigger;
				
				if(!$target.is('[data-cerb-trigger-chooser]'))
					return;
				
				if (e.eventData.exit === 'error') {

				} else if(e.eventData.exit === 'return') {
					Devblocks.interactionWorkerPostActions(e.eventData);
				}

				if(!e.eventData.return || !e.eventData.return.trigger)
					return;

				applyTrigger(e.eventData.return.trigger.id, e.eventData.return.trigger.name);
			}
		});


		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Gutter breakpoints: click a script line's gutter to toggle a red pip. On Run, each breakpoint is
		// synthesized into a throwaway `await/debug_<uid>:` node at that line so the simulator pauses there (the
		// paused state shows in Output), then Step resumes to the next. The editor text is never modified. The
		// breakpoint marker IS the source of truth (its alias rides in the marker's `data`), so the editor's own
		// line-anchoring keeps breakpoints glued to their line across edits — nothing here to keep in sync.
		var bpAlias = function() { return 'debug_' + (Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2)).slice(0, 8); };

		// `await:` is only legal as a child of a command-sequence container — that's where a breakpoint may sit.
		var BP_CONTAINERS = { 'start:':1, 'do:':1, 'on_error:':1, 'on_simulate:':1, 'on_success:':1, 'on_tool:':1, 'then:':1 };
		// `ed` is passed by the editor (it calls this DURING construction, before editor_automation is assigned).
		var bpClickable = function(row, ed) {
			ed = ed || editor_automation;
			if(!ed) return false;
			var path = ed.getPathForRow(row);   // segments incl. trailing ':' e.g. ['start:','set/x:']
			return path.length >= 2 && BP_CONTAINERS[path[path.length - 2]] === 1;
		};

		var editor_automation = new CerbUI.KataEditor($automation_yaml[0], {
			diffGutter: true,   // mark unsaved edits in the gutter vs the last save (reset on peek_saved below)
			commentDecorators: [
				{ caption: '@node', value: '@node ', hint: 'Node summary shown in the graph' }
			],
			gutterClickableRow: bpClickable,   // gates the click + the hover affordance to await-legal lines
			onGutterClick: function(row) {
				var mk = editor_automation.getMarkers().get(row);
				if(mk && mk.type === 'breakpoint') editor_automation.clearMarker(row);
				else editor_automation.setMarker(row, { type: 'breakpoint', data: bpAlias() });
			}{if is_a($extension, 'Extension_AutomationTrigger')}{$autocomplete_json = $extension->getAutocompleteSuggestionsJson()}{if is_string($autocomplete_json)},
			onAutocomplete: CerbUI.KataEditor.kataFieldSource({$autocomplete_json nofilter}){/if}{/if}
		});

		// Save-and-continue keeps this popup open (no re-render), so re-baseline the gutter diff to the just-saved
		// script — the change marks clear until the next edit. A full save closes the popup, so it needs no reset.
		$popup.on('peek_saved', function(e) { if(e.is_continue) editor_automation.resetDiffBaseline(); });

		// Build the script to RUN: the editor text with a throwaway `await/<alias>:` breakpoint node spliced in
		// before each breakpoint line, at that line's indentation. The editor itself is never touched.
		var scriptWithBreakpoints = function() {
			var markers = editor_automation.getMarkers();
			if(!markers.size) return editor_automation.getValue();
			var lines = editor_automation.getValue().split('\n');
			var rows = [];
			markers.forEach(function(mk, row) { if(mk.type === 'breakpoint' && mk.data) rows.push(row); });
			// Descending row order so earlier splices don't shift the not-yet-processed (higher) rows.
			rows.sort(function(a, b) { return b - a; }).forEach(function(row) {
				if(row < 0 || row >= lines.length || !bpClickable(row, editor_automation)) return;   // edited away from an await-legal line
				var line = lines[row];
				var indent = line.slice(0, line.length - line.trimStart().length);
				lines.splice(row, 0, indent + 'await/' + markers.get(row).data + ':\n' + indent + '  __breakpoint_line@int: ' + (row + 1));
			});
			return lines.join('\n');
		};

		{if $model->id}
		// Open the read-only changeset diff popup for the script; "Restore this version" writes a historical version back.
		var openChangesetsCode = function() {
			var formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'showChangesetsPopup');
			formData.set('record_type', 'automation');
			formData.set('record_id', '{$model->id}');
			formData.set('record_key', 'script');

			var $editor_code_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

			$editor_code_differ_popup.one('cerb-diff-viewer-ready', function(e) {
				e.stopPropagation();

				if(!e.hasOwnProperty('viewer'))
					return;

				e.viewer.setCurrent(editor_automation.getValue());

				e.viewer.onRestore(function(content) {
					editor_automation.setValue(content);
					editor_automation.clearSelection();
				});
			});
		};
		{/if}
		
		// Dot-notation is the wall most authors hit reading a simulated state, so both state panes let you drag a
		// key row's gutter grip into the script editor: the drag chip shows the placeholder you'll get, and the
		// editor writes the parent path for you. Drag only -- a click would insert wherever the caret happens to
		// be sitting, which is rarely where you meant.
		{literal}
		var insertPlaceholder = function(payload) {
			editor_automation.insertSnippet('{{' + payload.expr + '}}');
		};
		{/literal}
		var state_opts = { minLines: 15, maxLines: 15, dragKeys: true };

		// Run Input / Output — KataEditor (no autocompletion; KATA is a better fit than YAML here, though some
		// YAML-only keys aren't representable yet). Fixed 15-line height (was Ace setOption minLines/maxLines).
		var editor_state_start = new CerbUI.KataEditor($state_yaml[0], state_opts);
		var editor_state_end = new CerbUI.KataEditor($end_state_yaml[0], state_opts);

		// The script editor is the only drop target. The native caret tracks the pointer, so it doubles as the
		// insertion-point preview — no extra rendering.
		new CerbUI.Droppable(editor_automation.el, {
			accept: function(item, payload) { return !!(payload && payload.expr); },
			hoverClass: 'cerb-ui-kataeditor--drop-target',
			overlay: false,
			onMove: function(info) {
				var p = editor_automation.positionFromPoint(info.clientX, info.clientY);
				if(p) editor_automation.setCursorPosition(p.row, p.column, { scroll: false });
			},
			onDrop: function(info) { insertPlaceholder(info.payload); }
		});

		// Policy

		{if $model->id}
		// Open the read-only changeset diff popup for the policy; "Restore this version" writes a historical version back.
		var openChangesetsPolicy = function() {
			var formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'showChangesetsPopup');
			formData.set('record_type', 'automation');
			formData.set('record_id', '{$model->id}');
			formData.set('record_key', 'policy');

			var $editor_policy_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

			$editor_policy_differ_popup.one('cerb-diff-viewer-ready', function(e) {
				e.stopPropagation();

				if(!e.hasOwnProperty('viewer'))
					return;

				e.viewer.setCurrent(editor_policy.getValue());

				e.viewer.onRestore(function(content) {
					editor_policy.setValue(content);
					editor_policy.clearSelection();
				});
			});
		};
		{/if}

		// Generate a least-privilege policy from the live script (server scans the AST for privileged commands and
		// scopes each on the static literals it passes). Fills silently over an empty/stub policy; when a real policy
		// already exists, previews current → proposed in a widened confirm (CerbUI.DiffViewer) before replacing.
		var generatePolicy = function() {
			var fd = new FormData();
			fd.set('c', 'profiles');
			fd.set('a', 'invoke');
			fd.set('module', 'automation');
			fd.set('action', 'generatePolicy');
			fd.set('automation_script', editor_automation.getValue());
			fd.set('extension_id', $frm.find('input:hidden[name=extension_id]').val() || '');

			genericAjaxPost(fd, null, null, function(json) {
				if('object' !== typeof json) { Devblocks.createAlertError("An unexpected error occurred."); return; }
				if(json.error) { Devblocks.createAlertError(json.error); return; }

				var proposed = json.policy_kata;
				var current = editor_policy.getValue() || '';
				var apply = function() { editor_policy.setValue(proposed); editor_policy.clearSelection(); };

				// Empty/comment stub → just fill, no prompt.
				var hasRules = /(allow|deny)@bool/.test(current) || /^\s*(callers|settings)\s*:/m.test(current);
				if(!hasRules) { apply(); return; }

				// Real policy present → confirm with an inline diff of what will change.
				if(!(window.CerbUI && CerbUI.Confirm && CerbUI.DiffViewer)) {
					if(window.CerbUI && CerbUI.Confirm)
						CerbUI.Confirm.open({ title: 'Generate policy', body: 'Replace the current policy with a generated least-privilege policy?', confirmText: 'Replace', onConfirm: apply });
					else apply();
					return;
				}

				var body = document.createElement('div');
				var msg = document.createElement('div');
				msg.style.marginBottom = '0.75em';
				msg.textContent = 'Review the generated policy (right) against your current one (left) — the right side is editable if you want to merge. Replace?';
				body.appendChild(msg);
				var diffHost = document.createElement('div');
				diffHost.style.minHeight = '19em';   // reserve the ~12-row height so the modal centers before panes measure
				body.appendChild(diffHost);
				// left = current, right = proposed (before → after). The right pane is editable so the user can hand-merge;
				// the diff re-computes live. Panes defer measuring until the dialog reveals.
				var viewer = new CerbUI.DiffViewer(diffHost, { left: current, right: proposed, lines: 12, editableCurrent: true });

				CerbUI.Confirm.open({
					title: 'Generate policy',
					body: body,
					confirmText: 'Replace',
					width: 820,
					onConfirm: function() {
						editor_policy.setValue(viewer.getCurrent());   // the (possibly hand-merged) right pane
						editor_policy.clearSelection();
					}
				});
			});
		};

		// Policy editor — KataEditor with its integrated toolbar (Generate + Suggest + Change history via onAction; Help interaction).
		var editor_policy = new CerbUI.KataEditor($popup.find('textarea[name=automation_policy_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataAutomationPolicy),
			toolbar: {
				sections: [ $popup.find('[data-cerb-policy-toolbar]')[0] ],
				onAction: function(value, ed) {
					if(value === 'generate') { generatePolicy(); return true; }
					if(value === 'suggest') { ed.openAutocomplete(); return true; }
					{if $model->id}
					if(value === 'changesets') { openChangesetsPolicy(); return true; }
					{/if}
					return false;
				}
			}
		});

		var openExport = function() {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'showExportPopup');
			formData.set('fields[name]', $frm.find('input[name=name]').val());
			formData.set('fields[description]', $frm.find('input[name=description]').val());
			formData.set('fields[extension_id]', $frm.find('input[name=extension_id]').val());
			formData.set('fields[script]', editor_automation.getValue());
			formData.set('fields[policy_kata]', editor_policy.getValue());

			genericAjaxPopup('editorExport{$form_id}', formData, null, null, '60%');
		};

		var openFormBuilder = function() {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'showFormBuilderPopup');
			formData.set('extension_id', $frm.find('input[name=extension_id]').val());

			genericAjaxPopup('editorFormBuilder{$form_id}', formData, null, null, '90%');
		};

		// Open the `await:form:` from the current Run Input state as the ACTUAL interaction popup (the runtime
		// already resolved placeholders/types when it hit the await). Continue merges the filled values back into
		// the Input editor (element var → state key) so the next Run advances the continuation.
		var openFormStatePreview = function() {
			guideTo(null);   // the guide leaves the form icon the moment the popup opens

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'showFormStatePreviewPopup');
			formData.set('extension_id', $frm.find('input[name=extension_id]').val());
			formData.set('state', editor_state_start.getValue());

			var $p = genericAjaxPopup('editorFormPreview{$form_id}', formData, null, null, '640');

			// Closing the preview AT ALL (Continue, Start over, ✕, Esc) hands the guide to Run — you've seen the form,
			// so Run is the next step. Also covers preview-only forms (e.g. an auto-submit you can't submit here).
			$p.on('popup_close', function() { guideTo('play'); });

			// Continue → post the filled form + the LIVE Input state (the exact YAML that rendered the form) to
			// merge server-side, then drop the result back into the Input editor + close.
			$p.on('cerb-automation-form-fill-submit', function(e) {
				if(!e.form_el) return;

				var fd = new FormData(e.form_el);
				fd.set('c', 'profiles');
				fd.set('a', 'invoke');
				fd.set('module', 'automation');
				fd.set('action', 'submitFormStatePreview');
				fd.set('extension_id', $frm.find('input[name=extension_id]').val());
				fd.set('state', editor_state_start.getValue());

				genericAjaxPost(fd, null, null, function(yaml) {
					editor_state_start.setValue(yaml);
					editor_state_start.clearSelection();
					refreshFormButton();
					genericAjaxPopupClose($p);
				});
			});

			// Start over → just dismiss.
			$p.on('cerb-automation-form-fill-reset', function() {
				genericAjaxPopupClose($p);
			});
		};

		// The "Open form" button is contextual: only show it when the Input is parked at an await step whose
		// `__return`'s FIRST key is `form:` (a form await). await:interaction/map/etc. return other keys → no button.
		var $button_preview_form = $state_start_toolbar.find('.cerb-code-editor-toolbar-button--preview-form');
		var isFormAwaitState = function(val) {
			val = val || '';
			return /^__exit:\s*"?await"?\s*$/m.test(val) && /^__return:[ \t]*\r?\n[ \t]+form:/m.test(val);
		};
		var refreshFormButton = function() {
			$button_preview_form.toggle(isFormAwaitState(editor_state_start.getValue()));
		};
		$button_preview_form.on('click', openFormStatePreview);
		editor_state_start.onChange(refreshFormButton);
		refreshFormButton();

		// Clear buttons on the Input/Output strips — shown only when that pane has content. Clearing Output also
		// wipes the script editor's run line-markers (we're discarding the exit state they annotate) and hides Step.
		var $button_input_clear = $state_start_toolbar.find('.cerb-code-editor-toolbar-button--clear');
		var $button_output_clear = $state_end_toolbar.find('.cerb-code-editor-toolbar-button--clear');
		var refreshClearButtons = function() {
			$button_input_clear.toggle(!!(editor_state_start.getValue() || '').trim());
			$button_output_clear.toggle(!!(editor_state_end.getValue() || '').trim());
		};
		$button_input_clear.on('click', function() {
			editor_state_start.setValue('');
			editor_state_start.clearSelection();
			refreshFormButton();
			refreshClearButtons();
		});
		$button_output_clear.on('click', function() {
			editor_state_end.setValue('');
			editor_state_end.clearSelection();
			editor_automation.clearHighlight();
			$button_step.hide();
			refreshClearButtons();
		});
		editor_state_start.onChange(refreshClearButtons);
		editor_state_end.onChange(refreshClearButtons);
		refreshClearButtons();

		// "Compare to input" — a side-by-side diff of what the run actually changed, so you don't have to read two
		// YAML docs against each other. Posts the LIVE panes (not the last run's result) so it stays honest after a
		// hand-edit or a Step; the server canonicalizes both sides, since the panes are authored in different key
		// orders and comparing them as-authored would report a reordering as a rewrite.
		var $button_diff = $state_end_toolbar.find('.cerb-code-editor-toolbar-button--diff');
		var refreshDiffButton = function() {
			var has_both = !!(editor_state_start.getValue() || '').trim() && !!(editor_state_end.getValue() || '').trim();
			$button_diff.toggle(has_both);
		};
		$button_diff.on('click', function() {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'showStateDiffPopup');
			formData.set('input', editor_state_start.getValue());
			formData.set('output', editor_state_end.getValue());

			// The Output side's keys drag out as placeholders too. The dialog isn't modal, so the drop target is
			// the script editor behind it.
			genericAjaxPopup('editorStateDiff{$form_id}', formData, null, null, '80%');
		});
		editor_state_start.onChange(refreshDiffButton);
		editor_state_end.onChange(refreshDiffButton);
		refreshDiffButton();

		// Spectral tour guide: a single magic-sweep highlight that hops to the next action —
		//   'form' → the Open-form icon (a form is waiting in the Input); 'play' → Run (form saved, run to advance).
		var guideTo = function(where) {
			$button_preview_form.find('.cerb-icons').toggleClass('cerb-u-anim-magic-sweep', where === 'form');
			$button_run.find('.cerb-icons').toggleClass('cerb-u-anim-magic-sweep', where === 'play');
		};

		// One-shot guard so the prime flow's re-fired run isn't itself re-prompted (an empty primed result would
		// otherwise re-signal prime and loop). Set true right before re-firing Run after priming.
		var skipPrimeOnce = false;

		// Simulate with an empty Input → gather the initial state via a server-generated priming form (trigger
		// scope + automation #inputs, pre-filled with sane defaults), drop it into the Input editor, then re-run.
		var openPrimeState = function() {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'automation');
			formData.set('action', 'showPrimeStatePopup');
			formData.set('extension_id', $frm.find('input[name=extension_id]').val());
			formData.set('automation_script', editor_automation.getValue());

			var $p = genericAjaxPopup('editorPrimeState{$form_id}', formData, null, null, '520');

			$p.on('cerb-automation-prime-submit', function(e) {
				if(!e.form_el) return;

				var fd = new FormData(e.form_el);
				var mode = fd.get('prompts[prime]') || 'run';   // which button: 'run' | 'save'

				fd.set('c', 'profiles');
				fd.set('a', 'invoke');
				fd.set('module', 'automation');
				fd.set('action', 'submitPrimeState');
				fd.set('extension_id', $frm.find('input[name=extension_id]').val());
				fd.set('automation_script', editor_automation.getValue());

				genericAjaxPost(fd, null, null, function(yaml) {
					editor_state_start.setValue(yaml);
					editor_state_start.clearSelection();
					genericAjaxPopupClose($p);
					if(mode === 'run') {
						skipPrimeOnce = true;   // honor the primed result on the re-fired run (no re-prompt)
						$button_run.trigger('click');
					}
					// 'save': leave the primed state in the Input editor without running
				});
			});

			$p.on('cerb-automation-prime-reset', function() {
				genericAjaxPopupClose($p);
			});
		};

		{if $cursor}
		editor_automation.gotoLine({$cursor.row}, {$cursor.column});
		{/if}

		editor_automation.focus();

		$button_step
			.click(function() {
				Devblocks.clearAlerts();
				
				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'stepAutomationEditor');
				formData.set('output', editor_state_end.getValue());

				$spinner.insertAfter($button_step);
				$button_step.hide();

				genericAjaxPost(formData, null, null, function(yaml) {
					editor_state_start.setValue(yaml);
					editor_state_start.clearSelection();
					$spinner.detach();
					// A new await:form copied into the Input → resume the tour at the Open-form icon.
					if(isFormAwaitState(yaml)) guideTo('form');
				});

				editor_state_end.setValue('');
			})
		;

		$button_run
			.click(function() {
				Devblocks.clearAlerts();
				guideTo(null);   // tour complete — the user reached Run

				$spinner.insertAfter($button_run);
				$button_run.hide();
				$button_step.hide();

				editor_automation.clearHighlight();

				var formData = new FormData($frm[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'runAutomationEditor');
				formData.set('automation_script', scriptWithBreakpoints());   // splice in breakpoint awaits (editor unchanged)
				// The SERVER decides whether an empty simulate needs priming (trigger scope / #inputs); a bare
				// function or a no-input automation just runs. skip_prime = the prime flow already ran, so honor it.
				formData.set('skip_prime', skipPrimeOnce ? '1' : '');
				skipPrimeOnce = false;

				let cb = function(json) {
					$spinner.detach();
					$button_run.fadeIn();

					if('object' != typeof json) {
						Devblocks.createAlertError("An unexpected error occurred.");
						return;
					}

					// Empty simulate that needs input → gather it via the prime popup, which re-fires this run.
					if(json.exit === 'prime') {
						openPrimeState();
						return;
					}

					if(json.error) {
						Devblocks.createAlertError(json.error);
						return;
					}

					if(json.exit === 'await') {
						$button_step.fadeIn();
					}

					// Paused at a synthesized breakpoint → highlight that source line (orange = "stopped here"). The
					// breakpoint await carries `__breakpoint_line` = the original editor row+1, so map straight back.
					var bp_match = (json.exit === 'await') ? /__breakpoint_line:\s*(\d+)/.exec(json.dict || '') : null;

					if(bp_match) {
						var bp_row = parseInt(bp_match[1], 10) - 1;
						editor_automation.highlightLine(bp_row, { color: 'orange' });
						editor_automation.scrollToLine(bp_row);
					} else if(json.hasOwnProperty('exit_state')) {
						var state_path = json.exit_state;

						var row = editor_automation.getRowByPath(state_path);

						if(row) {
							// Green when the run finished cleanly; red is reserved for an error exit.
							editor_automation.highlightLine(row, { color: 'error' === json.exit ? 'red' : 'green' });
							editor_automation.scrollToLine(row);
						} else if(json.error_line) {
							// Parse/validation errors have no state path, but report a 1-based script line; mark it red.
							var error_row = json.error_line - 1;
							editor_automation.highlightLine(error_row, { color: 'red' });
							editor_automation.scrollToLine(error_row);
						}
					}

					editor_state_end.setValue(json.dict);
					editor_state_end.clearSelection();
				};

				let options = {
					'error': function(e) {
						$spinner.detach();
						$button_run.fadeIn();

						if(401 === e.status) {
							editor_state_end.setValue("error: Your session has expired.");
						} else if(403 === e.status) {
							editor_state_end.setValue("error: Permission denied.");
						} else if(504 === e.status) {
							editor_state_end.setValue("error: Execution of the automation timed out.");
						} else {
							editor_state_end.setValue("error: An unexpected error occurred.");
						}
					}
				};

				genericAjaxPost(formData, null, null, cb, options);
			})
		;

		$popup.on('cerb-automation-editor--goto', function(e) {
			if(!e.hasOwnProperty('editor_line'))
				return;

			var row = e.editor_line;

			if(false !== row) {
				editor_automation.scrollToLine(row);
				editor_automation.gotoLine(row+1);
				if(typeof editor_automation.flashLine === 'function') editor_automation.flashLine(row, { color: 'orange' });   // pulse the landing line
			}
		});

		// Formatting

		$toggle_mode.on('click', function() {
			if('simulator' === $toggle_mode.attr('data-mode')) {
				$state_start_toolbar.triggerHandler($.Event('cerb-editor-toolbar-mode-set', { simulator: false }));
			} else {
				$state_start_toolbar.triggerHandler($.Event('cerb-editor-toolbar-mode-set', { simulator: true }));
			}
		});

		$state_start_toolbar.on('cerb-editor-toolbar-mode-set', function(e) {
			if(e.hasOwnProperty('simulator')) {
				if(e.simulator) {
					$frm.find('input:hidden[name=is_simulator]').val('1');
					$toggle_mode.attr('data-mode', 'simulator');
					$toggle_mode.text('Simulate');
				} else {
					$frm.find('input:hidden[name=is_simulator]').val('0');
					$toggle_mode.attr('data-mode', 'live');
					$toggle_mode.text('Execute');
				}
			}
		});

		var doneFunc = function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger') || !e.eventData)
				return;

			if (e.eventData.exit === 'error') {

			} else if (e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData, editor_automation);
			}
		};

		var resetFunc = function(e) {
		}

		var errorFunc = function(e) {
		};

		// Script editor toolbar — one cerb-ui-toolbar strip: the static editor actions (Change history / Export /
		// Suggest) plus the per-trigger interaction items spliced in at the front. The dynamic items are re-rendered +
		// re-spliced + refresh()'d on trigger change (the source <ul> is preserved, so refresh() re-reads it).
		var toolbar_ul = $script_toolbar_wrapper.find('#script_toolbar_{$form_id}')[0];

		// Move the per-trigger interaction <li>s into the front of the strip (before the first static item). Null clears.
		var spliceToolbarDynamic = function(source_ul) {
			if(!toolbar_ul) return;
			toolbar_ul.querySelectorAll(':scope > li:not([data-static])').forEach(li => li.remove());
			if(source_ul) {
				var anchor = toolbar_ul.querySelector(':scope > li[data-static]');
				Array.from(source_ul.children).filter(n => n.tagName === 'LI').forEach(li => toolbar_ul.insertBefore(li, anchor));
			}
		};

		spliceToolbarDynamic($script_toolbar_wrapper.find('[data-cerb-toolbar-dynamic-source] > ul')[0]);

		var script_toolbar = (toolbar_ul && window.CerbUI && CerbUI.Toolbar) ? new CerbUI.Toolbar(toolbar_ul, {
			caller: {
				name: 'cerb.toolbar.editor.automation.script',
				params: {
					selected_text: ''
				}
			},
			width: '75%',
			start: function(formData) {
				var pos = editor_automation.getCursorPosition();
				var trigger = $frm.find('input:hidden[name=extension_id]').val();

				formData.set('caller[params][selected_text]', editor_automation.getSelectedText());
				formData.set('caller[params][token_path]', editor_automation.getTokenPath().join(''));
				formData.set('caller[params][cursor_row]', pos.row);
				formData.set('caller[params][cursor_column]', pos.column);
				formData.set('caller[params][trigger]', trigger);
				formData.set('caller[params][value]', editor_automation.getValue());
			},
			done: doneFunc,
			reset: resetFunc,
			error: errorFunc,
			// Static editor actions route by key; the per-trigger interaction items fire via cerbBotTrigger (done).
			onSelect: function(item) {
				switch(item.key) {
					case 'suggest': editor_automation.openAutocomplete(); break;
					{if $model->id}
					case 'changesets': openChangesetsCode(); break;
					{/if}
					case 'formbuilder': openFormBuilder(); break;
					case 'export': openExport(); break;
				}
			}
		}) : null;

		// ── Automation Builder: template picker (new records only) ───────────────────────────────
		// A new automation opens on this picker; the editor form is hidden until a candidate is applied or
		// skipped. Picking sets the trigger + seeds the ephemeral (id 0) editor — nothing is saved until Save.
		{literal}
		(function() {
			var $picker = $frm.find('[data-cerb-automation-template-picker]');
			if(!$picker.length) return;

			var $body = $frm.find('[data-cerb-automation-editor-body]');
			var $browse = $picker.find('[data-cerb-template-browse]');
			var $wizard = $picker.find('[data-cerb-template-wizard]');
			var $wizardBody = $picker.find('[data-cerb-template-wizard-body]');
			var $apply = $picker.find('[data-cerb-template-apply]');
			var $applyStrip = $picker.find('[data-cerb-template-apply-strip]');

			var current = null;   // template extension id

			// Filter cards by the search box; hide groups with no visible card.
			$picker.find('.cerb-ab-template-search').on('input', function() {
				var q = (this.value || '').trim().toLowerCase();
				$picker.find('[data-cerb-template-card]').each(function() {
					var hit = !q || (this.getAttribute('data-search') || '').indexOf(q) !== -1;
					this.style.display = hit ? '' : 'none';
				});
				$picker.find('[data-cerb-template-group]').each(function() {
					this.style.display = $(this).find('[data-cerb-template-card]:visible').length ? '' : 'none';
				});
			});

			// Reveal the editor. It was built while hidden (zero-size), so nudge each KATA editor to re-render.
			var revealEditor = function() {
				$picker.hide();
				$body.prop('hidden', false).show();
				[editor_automation, editor_policy, editor_state_start, editor_state_end].forEach(function(ed) {
					if(ed && ed.setValue) ed.setValue(ed.getValue());
				});
				$body.find('input[name=name]').trigger('focus');
			};

			// Seed the editor from a built template (server generates script/policy from any answers), then reveal.
			var seedFromTemplate = function(template_id, answers) {
				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'applyTemplate');
				formData.set('template_id', template_id);
				if(answers) {
					Object.keys(answers).forEach(function(k) {
						var v = answers[k];
						formData.set('answers[' + k + ']', (v !== null && typeof v === 'object') ? JSON.stringify(v) : v);
					});
				}

				genericAjaxPost(formData, null, null, function(json) {
					if(!json || !json.status) {
						alert((json && json.error) || 'Failed to apply the template.');
						return;
					}
					if(json.extension_id)
						applyTrigger(json.extension_id, json.extension_name || json.extension_id);
					editor_automation.setValue(json.script || '');
					editor_automation.clearSelection();
					editor_policy.setValue(json.policy_kata || '');
					editor_policy.clearSelection();
					revealEditor();
				});
			};

			// Pick a candidate: a wizard candidate loads its config slide-in; otherwise apply immediately.
			$picker.on('click', '[data-cerb-template-card]', function() {
				var template_id = this.getAttribute('data-template-id');
				current = template_id;

				if(this.getAttribute('data-has-wizard') !== '1') {
					seedFromTemplate(template_id, null);
					return;
				}

				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'automation');
				formData.set('action', 'showTemplateWizard');
				formData.set('template_id', template_id);

				$wizardBody.empty().append(Devblocks.getSpinner());
				$browse.hide();
				$wizard.prop('hidden', false).show();
				$applyStrip.prop('hidden', false).show();

				genericAjaxPost(formData, $wizardBody);
			});

			// Enter/Space on a focused card = click.
			$picker.on('keydown', '[data-cerb-template-card]', function(e) {
				if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
			});

			// Wizard: back to the list.
			$picker.on('click', '[data-cerb-template-back]', function() {
				$wizard.prop('hidden', true).hide();
				$applyStrip.prop('hidden', true).hide();
				$browse.show();
				$wizardBody.empty();
				current = null;
			});

			// Wizard: apply — a wizard's own script may expose _cerbValidate() (block on invalid) and
			// _cerbGetAnswers() on the body element.
			$apply.on('click', function() {
				if(!current) return;
				var bodyEl = $wizardBody[0];
				if(bodyEl && typeof bodyEl._cerbValidate === 'function' && !bodyEl._cerbValidate()) return;
				var answers = (bodyEl && typeof bodyEl._cerbGetAnswers === 'function') ? bodyEl._cerbGetAnswers() : {};
				seedFromTemplate(current, answers);
			});

			// Skip: fall through to the blank editor (blank trigger + default script), as before.
			$picker.on('click', '[data-cerb-template-skip]', function() {
				revealEditor();
			});
		})();
		{/literal}

		// Agent pane — a collapsible chat astride the WHOLE automation editor so an interaction can drive the UI:
		// getFields (name/description/trigger/script/policy), setField (whole field by key), editField (undo-safe
		// exactly-once search/replace on script/policy), grepField (locate query → line + KATA key path), changeTab
		// (run/policy/log/visualization/usage), highlightLine / highlightKey (nav by line or KATA path). Built here
		// (end of init) so the closure has the editors + tabs. Empty toolbar → the pane hides its toggle.
		if(window.CerbUI && CerbUI.AgentPane) {
			const automationTabsEl = $frm.find('[data-cerb-automation-editor-tabs] > ul')[0];
			const automationTabNames = automationTabsEl
				? Array.from(automationTabsEl.querySelectorAll(':scope > li[data-cerb-tab]')).map(li => li.getAttribute('data-cerb-tab'))
				: [];

			const agentDlg0 = CerbUI.Dialog ? CerbUI.Dialog.from($popup[0]) : null;
			const agentDlgOrigPct = (agentDlg0 && agentDlg0._widthPct) ? agentDlg0._widthPct : 80;

			// Only the two KATA code editors take surgical edit/grep/highlight-by-key; name/desc/trigger are setField-only.
			// The edit/grep logic itself is shared: CerbUI.editorCore.applyUniqueEdit / grepEditor.
			const codeEditorForKey = function(key) {
				if(key === 'script') return editor_automation;
				if(key === 'policy') return editor_policy;
				return null;
			};

			const agentPane = new CerbUI.AgentPane(document.getElementById('automationAgentMount{$form_id}'), {
				component: 'automation',
				capabilities: 'getFields,setField,editField,grepField,getDiff,changeTab,highlightLine,highlightKey',
				// Commands that WRITE the editor → arm AgentPane's navigation guard (Cmd+[/back/reload) so the
				// agent's programmatic edits — which never trip the form's keystroke dirty check — aren't lost.
				mutatingCommands: 'setField,editField',
				toolbarHtml: {$agent_toolbar_html_json|default:'""' nofilter},
				storageKey: 'cerb-automation-editor-agent-chat',
				fit: true, // popup: keep the editor's natural height; just add a sidebar
				// Put the toggle on the right of the script toolbar strip (above the code editor).
				toggleInto: $frm.find('[data-cerb-automation-script-toolbar]')[0] || null,
				onToggle: function(collapsed) {
					// Widen the editor dialog to make room for the chat; restore on close.
					const dlg = (CerbUI.Dialog && $popup.length) ? CerbUI.Dialog.from($popup[0]) : null;
					if(!dlg) return;
					dlg._widthPct = collapsed ? agentDlgOrigPct : 97;
					dlg.w = dlg._computeWidth();
					dlg.el.style.width = dlg.w + 'px';
					dlg._positionDefault();
					CerbUI.Dialog._syncPageHeight();
				},
				runCommand: function(name, params) {
					params = params || {};
					if(name === 'getFields') {
						return JSON.stringify({
							name:        $frm.find('input[name=name]').val(),
							description: $frm.find('input[name=description]').val(),
							trigger:     $frm.find('input:hidden[name=extension_id]').val() || '',
							script:      editor_automation.getValue(),
							policy:      editor_policy.getValue()
						});
					}
					if(name === 'setField') {
						const key = params.key, value = (params.value == null) ? '' : String(params.value);
						switch(key) {
							case 'name': case 'description':
								$frm.find('input[name=' + key + ']').val(value);
								return 'ok';
							case 'trigger':
								applyTrigger(value, value);   // extension_id (bubble shows the id, matching the static render)
								return 'ok';
							case 'script':
								editor_automation.setValue(value); editor_automation.clearSelection();
								return 'ok';
							case 'policy':
								editor_policy.setValue(value); editor_policy.clearSelection();
								return 'ok';
						}
						return 'unknown field: ' + key;
					}
					if(name === 'editField') {
						const editor = codeEditorForKey(params.key);
						if(!editor) return 'error: editField supports only key script or policy; use setField for ' + params.key + '.';
						return CerbUI.editorCore.applyUniqueEdit(editor, (params.old == null) ? '' : String(params.old), (params.new == null) ? '' : String(params.new));
					}
					if(name === 'grepField') {
						const editor = codeEditorForKey(params.key);
						if(!editor) return 'error: grepField supports only key script or policy.';
						const query = (params.query == null) ? '' : String(params.query);
						if(query === '') return 'error: grepField needs a non-empty query.';
						return CerbUI.editorCore.grepEditor(editor, query, parseInt(params.limit, 10));
					}
					if(name === 'getDiff') {
						// The UNSAVED diff vs the last save — lets the agent see what changed without loading the
						// Change History popup. Lines are 1-based to match highlightLine/grepField. `tracked` is false
						// for a field that carries no baseline (nothing to diff yet).
						const editor = codeEditorForKey(params.key || 'script');
						if(!editor || typeof editor.getDiffState !== 'function')
							return 'error: getDiff supports only key script or policy.';
						const st = editor.getDiffState();
						return JSON.stringify({
							key:     params.key || 'script',
							tracked: st.baseline != null,
							hunks:   st.hunks.map(function(h) {
								return {
									status:  h.status,          // 'added' | 'modified' | 'deleted'
									line:    h.rowStart + 1,    // 1-based first current line of the hunk
									endLine: h.rowEnd,          // 1-based inclusive last current line (< line for a pure deletion)
									added:   h.added,           // current lines introduced/changed
									removed: h.removed          // baseline lines replaced/removed
								};
							})
						});
					}
					if(name === 'changeTab') {
						const tabs = (CerbUI.Tabs && automationTabsEl) ? CerbUI.Tabs.from(automationTabsEl) : null;
						const idx = automationTabNames.indexOf(params.tab);
						if(tabs && idx >= 0) { tabs.select(idx); return 'ok'; }
						return 'unknown tab: ' + params.tab;
					}
					if(name === 'highlightLine') {
						// Scroll the script editor to a line and pulse it — "where is X?" navigation. `line` is 1-based.
						const line = parseInt(params.line, 10);
						if(isNaN(line) || line < 1) return 'invalid line: ' + params.line;
						const row = line - 1;
						editor_automation.scrollToLine(row);
						editor_automation.gotoLine(line);
						if(typeof editor_automation.flashLine === 'function') editor_automation.flashLine(row, { color: 'orange' });
						return 'ok';
					}
					if(name === 'highlightKey') {
						// Flash the row for a KATA key path (e.g. start:while:do:llm.agent) — robust nav for KATA where
						// line counting is unreliable. Pairs with grepField, which reports the path to jump to.
						const editor = codeEditorForKey(params.key || 'script');
						if(!editor) return 'error: highlightKey supports only key script or policy.';
						const row = editor.getRowByPath(String(params.path || ''));
						if(row === false || row < 0) return 'error: no row for path: ' + params.path;
						editor.scrollToLine(row);
						editor.gotoLine(row + 1);
						if(typeof editor.flashLine === 'function') editor.flashLine(row, { color: 'orange' });
						return 'ok';
					}
					return '';
				}
			});

			// A successful save persists the agent's edits → drop the unsaved-navigation guard. (A discard-close
			// is handled by the pane's own DOM-detach prune in AgentPane._pruneDirty.)
			$popup.on('peek_saved', function() { if(agentPane && agentPane.markClean) agentPane.markClean(); });
		}
	});
});
</script>
