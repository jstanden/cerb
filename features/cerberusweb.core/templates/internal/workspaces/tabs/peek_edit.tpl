{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_TAB}
{$peek_context_id = $model->id|default:0}
{$form_id = uniqid()}
{$page = $model->getWorkspacePage()}
{$tab_extension = $tab_extensions[$model->extension_id|default:'']}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-placeholders>
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="workspace_tab">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($peek_context_id)}<input type="hidden" name="id" value="{$peek_context_id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$peek_context_id}
	{if $page}
	<input type="hidden" name="workspace_page_id" value="{$page->id}">
	{else}
	<div class="cerb-ui-form" style="margin-bottom:5px;">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.page'|devblocks_translate|capitalize}</label>
			<div>
				<div class="cerb-ui-record-chooser" id="workspacePageChooser_{$form_id}">
					{if $model->workspace_page_id && $page}
						<li data-context-id="{$page->id}" data-label="{$page->name}"></li>
					{/if}
				</div>
			</div>
		</div>
	</div>
	{/if}
{/if}

<div class="cerb-tabs">
	{if !$peek_context_id}
	<ul id="tabTabs_{$form_id}">
		{if $packages}<li><a href="#tab-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#tab-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#tab-import_{$form_id}">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}

	{if !$peek_context_id && $packages}
	<div id="tab-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}

	{if !$peek_context_id}
	<div id="tab-import_{$form_id}">
		<textarea name="import_json" style="width:100%;height:250px;box-sizing:border-box;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a workspace tab in JSON format"></textarea>

		<div>
			<button type="button" class="cerb-ui-button import"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}

	<div id="tab-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					<div>
						{if $peek_context_id && $tab_extension}
							{$tab_extension->params.label|devblocks_translate|capitalize}
						{else}
							<select name="extension_id">
								<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
								{foreach from=$tab_extensions item=tab_ext}
									<option value="{$tab_ext->id}">{$tab_ext->params.label|devblocks_translate|capitalize}</option>
								{/foreach}
							</select>
						{/if}
					</div>
				</div>

				{if !empty($custom_fields)}
				{* bulk/form.tpl with tbody=true emits <tbody> rows → needs a <table> wrapper *}
				<table cellspacing="0" cellpadding="2" border="0" width="98%">
					{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				</table>
				{/if}
			</div>
		</div>

		{* The rest of config comes from the tab extension *}
		<div class="cerb-tab-params">
		{if $tab_extension}
			{$tab_ext = Extension_WorkspaceTab::get($tab_extension->id, true)}
			{if $tab_ext && method_exists($tab_ext,'renderTabConfig')}
				{$tab_ext->renderTabConfig($page, $model)}
			{/if}
		{/if}
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$peek_context_id}

		<div data-cerb-fieldset-advanced class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--label">Advanced options</div>
			</div>
			<ul class="cerb-ui-toolbar" id="advancedToolbar_{$form_id}">
				<li data-icon="autocomplete" data-value="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl+Space)"></li>
			</ul>
			<textarea id="advancedEditor_{$form_id}" name="options_kata" class="placeholders" data-editor-lines="4" spellcheck="false">{$model->options_kata}</textarea>
		</div>

		<div class="cerb-placeholder-menu" style="display:none;">
		{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/toolbar.tpl"}
		</div>

		{if !empty($peek_context_id)}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="workspace tab"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($peek_context_id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button delete-prompt"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	let $params = $frm.find('div.cerb-tab-params');
	let $fieldset_advanced = $frm.find('[data-cerb-fieldset-advanced]');

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.workspace.tab'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Options Editor — flush its full document into the textarea before the form serializes
		let syncEditors = function() {
			$popup.find('[name=options_kata]').val(advanced_editor.getValue());
		};

		// Buttons
		$popup.find('button.save').click({ before: syncEditors }, Devblocks.callbackPeekEditSave);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Inline delete confirm (reveals the cerb-ui-panel--alert, hides the button row); the actual
		// delete stays on button.delete above.
		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);

		// Tabs + Package Library

		{if !$peek_context_id}
			let tabs_ul = $popup.find('#tabTabs_{$form_id}')[0];
			if(tabs_ul && window.CerbUI && CerbUI.Tabs)
				new CerbUI.Tabs(tabs_ul);

			{if $packages}
				var $library_container = $popup.find('.cerb-tabs');
				{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

				$library_container.on('cerb-package-library-form-submit', function(e) {
					$popup.one('peek_saved peek_error', function(e) {
						$library_container.triggerHandler('cerb-package-library-form-submit--done');
					});

					$popup.find('button.save').click();
				});
			{/if}
		{/if}

		// Abstract choosers
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#workspacePageChooser_{$form_id}')[0], { context: '{CerberusContexts::CONTEXT_WORKSPACE_PAGE}', name: 'workspace_page_id', emptyIcon: 'window-left', query: 'type:"core.workspace.page.workspace"' });

		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Switching extension params
		var $select = $popup.find('select[name=extension_id]');

		$select.on('change', function() {
			const extension_id = $select.val();

			if(!extension_id) {
				$params.empty();
				return;
			}

			// Fetch via Ajax
			genericAjaxGet($params, 'c=profiles&a=invoke&module=workspace_tab&action=getTabParams&page_id={$page->id}&tab_id={$peek_context_id}&extension=' + encodeURIComponent(extension_id), function() {
				$params.find('.cerb-peek-trigger').cerbPeekTrigger();
			});
		});

		// Options Editor

		let advanced_editor = new CerbUI.KataEditor($fieldset_advanced.find('#advancedEditor_{$form_id}')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource({literal}{
				'': [
					'hidden@bool:',
					'locked@bool:'
				],
				'hidden:': [
					'yes',
					'no',
					'{{worker_id == 123}}',
					'{{not worker_is_superuser}}'
				],
				'locked:': [
					'yes',
					'no',
					'{{worker_id == 123}}',
					'{{not worker_is_superuser}}'
				]
			}{/literal})
		});

		let advanced_toolbar = $fieldset_advanced.find('#advancedToolbar_{$form_id}')[0];
		if(advanced_toolbar && window.CerbUI && CerbUI.Toolbar) {
			new CerbUI.Toolbar(advanced_toolbar, {
				bare: false,
				onSelect: function(item) {
					if('autocomplete' === item.value)
						advanced_editor.openAutocomplete();
				}
			});
		}
	});
});
</script>
