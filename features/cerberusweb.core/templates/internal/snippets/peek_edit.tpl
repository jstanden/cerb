{$peek_context = CerberusContexts::CONTEXT_SNIPPET}
{$peek_context_id = $model->id}
{$frm_id = "form{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$frm_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="snippet">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
		</td>
		<td width="99%">
			<input type="text" name="title" value="{$model->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;" autofocus="autofocus"><br>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
		</td>
		<td width="99%">
			<select name="context">
				<option value="" {if empty($model->id)}selected="selected"{/if}>Plaintext</option>
				{foreach from=$contexts item=ctx key=k}
				{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
				<option value="{$k}" {if $model->context==$k}selected="selected"{/if}>{$ctx->name}</option>
				{/if}
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
		</td>
	</tr>
</table>

<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;">{$model->content}</textarea>
<div class="toolbar"></div>

<fieldset class="peek placeholders" style="margin-top:10px;">
	<legend>{'common.prompts'|devblocks_translate|capitalize}: <small>(KATA)</small></legend>

	<div class="cerb-code-editor-toolbar">
		<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-run"><span class="glyphicons glyphicons-play"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
		<ul class="cerb-float" style="display:none;">
			<li data-type="checkbox">Checkbox</li>
			<li data-type="picklist">Picklist</li>
			<li data-type="text">Text</li>
		</ul>
		<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/snippets/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
	</div>
	<textarea name="prompts_kata" class="cerb-editor-kata-placeholders" data-editor-mode="ace/mode/cerb_kata">{$model->prompts_kata}</textarea>
	<div class="cerb-code-editor-preview-output"></div>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek" style="margin-top:10px;">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if isset($model->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this snippet?</legend>
	<p>Are you sure you want to permanently delete this snippet?</p>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$frm_id}');
	var $popup = genericAjaxPopupFind($frm);

	$popup.one('popup_open', function (event) {
		event.stopPropagation();

		$popup.dialog('option', 'title', '{'common.snippet'|devblocks_translate|capitalize|escape:'javascript'}');
		$popup.css('overflow', 'inherit');

		var $textarea = $popup.find('textarea[name=content]');

		var $editor = $popup.find('.cerb-editor-kata-placeholders')
			.cerbCodeEditor()
			.nextAll('pre.ace_editor')
		;

		var editor = ace.edit($editor.attr('id'));

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Owners

		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');

		$ul.on('bubble-remove', function (e) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});

		$owners_menu.menu({
			select: function (event, ui) {
				event.stopPropagation();

				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');

				if (undefined == token || undefined == label)
					return;

				$owners_menu.hide();

				// Build bubble

				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context', context_data[0]).attr('data-context-id', context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				$('<input type="hidden">').attr('name', 'owner').attr('value', token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				$('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);

				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});

		// Change

		var $change_dropdown = $popup.find("form select[name=context]");
		$change_dropdown.change(function (e) {
			var ctx = $(this).val();
			genericAjaxGet($popup.find('DIV.toolbar'), 'c=profiles&a=invoke&module=snippet&action=renderToolbar&form_id={$frm_id}&context=' + ctx);
		});

		// If editing and a target context is known
		genericAjaxGet($popup.find('DIV.toolbar'), 'c=profiles&a=invoke&module=snippet&action=renderToolbar&form_id={$frm_id}&context={$model->context}');

		// Snippet syntax
		$textarea
			.cerbTextEditor()
		;

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
		var $menu_add = $button_add.next('ul');

		$button_add.on('click', function () {
			$menu_add.toggle();
		});

		$menu_add.menu({
			select: function (e, ui) {
				e.stopPropagation();

				var $li = $(ui.item);
				var type = $li.attr('data-type');
				var snippet = '';

				$menu_add.hide();

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

				$editor.triggerHandler($.Event('cerb.appendText', { content: snippet }));
			}
		});

	});
});
</script>