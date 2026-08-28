{$peek_context = 'cerb.contexts.agent.tool'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{$status = $model->status|default:0}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-dialog-title="{'Agent Tool'|devblocks_translate|capitalize}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_tool">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize} <span class="cerb-ui-form--required">*</span></label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus" autocomplete="off" spellcheck="false" placeholder="web_search">
				<div class="cerb-ui-form--hint">The name the model calls. Lowercase letters, numbers, and underscores. <code>cerb_</code> is reserved for Cerb's own tools.</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.label'|devblocks_translate|capitalize}</label>
				<input type="text" name="label" value="{$model->label}" placeholder="Web Search">
				<div class="cerb-ui-form--hint">Optional. What people call it. Defaults to the name.</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
			<textarea name="description" rows="3" spellcheck="false">{$model->description}</textarea>
			<div class="cerb-ui-form--hint">What the model reads to decide whether to call this tool. Say what it does and when to use it.</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.automation'|devblocks_translate|capitalize}</label>
			<div>
				<div class="cerb-ui-record-chooser" id="toolAutomation_{$form_id}">
					{if !empty($automation)}<li data-context-id="{$automation->id}" data-label="{$automation->name}"></li>{/if}
				</div>
			</div>
			<div class="cerb-ui-form--hint">The <code>agent.tool</code> automation that does the work and returns a <code>content</code> string. Optional -- leave it empty and the calling conversation answers the tool itself from its <code>on_tool:</code> branch.</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="status" id="status_{$form_id}" value="{$status}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="status_{$form_id}">
					<button type="button" data-value="0"{if $status == 0} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> Available</button>
					<button type="button" data-value="1"{if $status == 1} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lock"></span> Unlisted</button>
					<button type="button" data-value="2"{if $status == 2} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.disabled'|devblocks_translate|capitalize}</button>
				</div>
			</div>
			<div class="cerb-ui-form--hint">Unlisted hides it from choosers but still runs when an agent names it. Disabled refuses it everywhere.</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-bot-message"></span> In the transcript</div>
	</div>
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.icon'|devblocks_translate|capitalize}</label>
				<input type="text" name="icon" value="{$model->icon}" id="iconInput_{$form_id}" autocomplete="off" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_tool.label_active'|devblocks_translate|capitalize}</label>
				<input type="text" name="label_active" value="{$model->label_active}" placeholder="Searching the web...">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.agent_tool.label_summary'|devblocks_translate|capitalize}</label>
				<input type="text" name="label_summary" value="{$model->label_summary}" placeholder="Searched the web">
			</div>
		</div>
		<div class="cerb-ui-form--hint">Either label may use <code>&#123;&#123;placeholders&#125;&#125;</code> from the tool's own parameters, e.g. <code>Searched for &#123;&#123;query&#125;&#125;</code>.</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-adjust"></span> {'common.parameters'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<textarea name="params_kata" id="paramsKata_{$form_id}" rows="10" spellcheck="false">{$model->params_kata}</textarea>
			<div class="cerb-ui-form--hint"><code>parameters:</code> is what the model may send -- each with a description, and optionally <code>enum</code>, <code>required</code>, and a <code>default</code> for when the model leaves it out. Values the model must NOT choose belong on the agent's reference to this tool, not here.</div>
		</div>

		{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="agent tool" detail="Agents that reference it will lose it."}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		// Posts `automation_id`; the save action turns it back into `cerb:automation:<name>` so a later rename
		// of the automation keeps the tool connected.
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser(document.getElementById('toolAutomation_{$form_id}'), {
				context: 'cerb.contexts.automation',
				name: 'automation_id',
				emptyIcon: 'zap',
				query: 'trigger:cerb.trigger.agent.tool'
			});

		const iconEl = document.getElementById('iconInput_{$form_id}');

		if(iconEl && window.CerbUI && CerbUI.IconPicker) {
			new CerbUI.IconPicker(iconEl, {
				emptyIcon: 'wrench',
				allowClear: true
			});
		}

		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				const input = document.getElementById(this.getAttribute('data-cerb-input'));
				if(!input) return;

				new CerbUI.Switcher(this, {
					value: input.value,
					onSelect: v => input.value = v
				});
			});
		}

		if(window.CerbUI && CerbUI.KataEditor) {
			const paramsEl = document.getElementById('paramsKata_{$form_id}');

			if(paramsEl)
				new CerbUI.KataEditor(paramsEl, { minLines: 8, maxLines: 24 });
		}

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);
	});
});
</script>
