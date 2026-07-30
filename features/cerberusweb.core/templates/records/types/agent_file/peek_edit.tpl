{$peek_context = 'cerb.contexts.agent.file'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_file">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'dao.agent_file.filesystem_id'|devblocks_translate|capitalize}</label>
			<div>
				<div class="cerb-ui-record-chooser" id="agentfile_fs_{$form_id}">
					{if !empty($filesystem)}<li data-context-id="{$filesystem->id}" data-label="{$filesystem->name}"></li>{/if}
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus" placeholder="path/to/file.md">
			<div class="cerb-ui-form--help">The file's path within the filesystem, e.g. <code>references/refunds.md</code>.</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'dao.agent_file.content'|devblocks_translate|capitalize}</div>
	</div>
	{* Host toolbar section (preview) — merged in after the editor's built-in formatting strip. *}
	<ul class="cerb-ui-toolbar" data-cerb-agentfile-toolbar hidden>
		<li data-value="preview" data-icon="eye-open" title="Preview"></li>
	</ul>
	<textarea name="content" data-editor-lines="20" spellcheck="false">{$model->content}</textarea>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="agent file"}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Agent File'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		// Filesystem picker (server-backed, ACL-filtered) — posts filesystem_id
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($frm.find('#agentfile_fs_{$form_id}')[0], {
				context: 'agent_filesystem',
				name: 'filesystem_id',
				emptyIcon: 'folder',
				searchPlaceholder: "{'dao.agent_file.filesystem_id'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

		// Render a markdown preview via the generic c=ui&a=markdownPreview endpoint (parseMarkdown + purifyHTML)
		var previewContent = function(value) {
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'markdownPreview');
			formData.set('content', value);
			formData.set('frontmatter', 'table'); // render leading YAML frontmatter as a key/value table
			formData.set('inline', '0');          // popup (gets a real Dialog title, not "Loading...")
			genericAjaxPopup('preview_agentfile', formData, 'reuse', false);
		};

		// Markdown body editor (textarea[name=content] stays the value holder)
		let ed = null;
		if(window.CerbUI && CerbUI.MarkdownEditor)
			ed = new CerbUI.MarkdownEditor($popup.find('textarea[name=content]')[0], {
				mode: 'markdown',
				images: false,
				diffGutter: true,   // full-width body bands mark what changed since the file was opened

				toolbar: {
					sections: [ $popup.find('[data-cerb-agentfile-toolbar]')[0] ],
					onAction: function(value, ed) {
						if(value === 'preview') { previewContent(ed.getValue()); return true; }
						return false; // bold/italic/… run their built-in
					}
				}
			});

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Save-and-continue keeps the popup open — re-baseline the diff so the bands mark only edits made AFTER the
		// save (a full save closes the popup; create+continue reloads it, recapturing the baseline either way).
		$popup.on('peek_saved', function() { if(ed && typeof ed.resetDiffBaseline === 'function') ed.resetDiffBaseline(); });

		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);
	});
});
</script>
