{$peek_context = CerberusContexts::CONTEXT_SNIPPET}
{$peek_context_id = $model->id}
{$frm_id = "form{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$frm_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="snippet">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
				<input type="text" name="title" value="{$model->title}" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				<select name="context">
					{* Plaintext intentionally has no icon so it stands out from the record types *}
					<option value="" {if empty($model->id)}selected="selected"{/if}>Plaintext</option>
					{foreach from=$contexts item=ctx key=k}
					{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
					<option value="{$k}" data-cerb-ui-icon="{$ctx->params.icon|default:'collection'}" {if $model->context==$k}selected="selected"{/if}>{$ctx->name}</option>
					{/if}
					{/foreach}
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.content'|devblocks_translate|capitalize}</div>
	</div>
	<textarea name="content" style="width:100%;height:200px;">{$model->content}</textarea>
	<div class="toolbar"></div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.prompts'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">(KATA)</small></div>
	</div>

	<div class="cerb-code-editor-toolbar">
		<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-run"><span class="cerb-icons cerb-icon-play"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-add"><span class="cerb-icons cerb-icon-circle-plus"></span></button>
		<ul class="cerb-float" style="display:none;">
			<li data-type="checkbox"><div>Checkbox</div></li>
			<li data-type="picklist"><div>Picklist</div></li>
			<li data-type="text"><div>Text</div></li>
		</ul>
		<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/snippets/" target="_blank"><span class="cerb-icons cerb-icon-circle-question-mark"></span></a></button>
	</div>
	<textarea name="prompts_kata" data-editor-lines="15" spellcheck="false">{$model->prompts_kata}</textarea>
	<div class="cerb-code-editor-preview-output"></div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if isset($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="snippet"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$frm_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function (event) {
		event.stopPropagation();

		$popup.dialog('option', 'title', '{'common.snippet'|devblocks_translate|capitalize|escape:'javascript'}');
		$popup.css('overflow', 'inherit');

		var $textarea = $popup.find('textarea[name=content]');

		var editor = new CerbUI.KataEditor($popup.find('textarea[name=prompts_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource({CerberusApplication::kataAutocompletions()->snippetPrompt()|json_encode nofilter})
		});

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Type — native <select> keeps the POST value; enhance with type-to-filter (re-fires change below)
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('form select[name=context]').each(function() { new CerbUI.SelectMenu(this); });

		// Change

		var $change_dropdown = $popup.find("form select[name=context]");
		$change_dropdown.change(function (e) {
			var ctx = $(this).val();
			genericAjaxGet($popup.find('DIV.toolbar'), 'c=profiles&a=invoke&module=snippet&action=renderToolbar&form_id={$frm_id}&context=' + ctx);
		});

		// If editing and a target context is known
		genericAjaxGet($popup.find('DIV.toolbar'), 'c=profiles&a=invoke&module=snippet&action=renderToolbar&form_id={$frm_id}&context={$model->context}');

		var $placeholder_output = $popup.find('.cerb-code-editor-preview-output');

		$popup.find('.cerb-editor-button-run').on('click', function (e) {
			$placeholder_output.html('');

			Devblocks.getSpinner().appendTo($placeholder_output);

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'snippet');
			formData.set('action', 'renderPrompts');
			formData.set('prompts_kata', editor.getValue());

			genericAjaxPost(formData, null, null, function (html) {
				$placeholder_output.html(html);
			});
		});

		var $button_add = $frm.find('.cerb-editor-button-add');
		var $menu_add = $button_add.next('ul').hide();

		new CerbUI.Menu($menu_add[0], {
			clickTrigger: $button_add[0],
			filter: true,
			onSelect: function (li, src) {
				var type = src.getAttribute('data-type');
				var snippet = '';

				{literal}
				if ('checkbox' === type) {
					snippet = "checkbox/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
							"  label: ${2:Checkbox}:\n" +
							"  default@bool: yes\n" +
							"\n"
					;
				} else if ('picklist' === type) {
					snippet = "picklist/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
							"  label: ${2:Picklist}:\n" +
							"  default: ${3:green}\n" +
							"  params:\n" +
							"    options@list:\n" +
							"      ${4:red}\n" +
							"      green\n" +
							"      blue\n" +
							"\n"
					;
				} else if ('text' === type) {
					snippet = "text/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
							"  label: ${2:Text}:\n" +
							"  default: ${3:text}\n" +
							"  params:\n" +
							"    multiple@bool: no\n" +
							"\n"
					;
				}
				{/literal}

				// insertSnippet accepts Ace-format snippets: it flattens numbered tab-stops to their defaults and lands the caret at the first.
				editor.insertSnippet(snippet);
			}
		});

	});
});
</script>