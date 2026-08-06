{$peek_context = CerberusContexts::CONTEXT_PACKAGE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="package">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Building packages</div>
				<div class="cerb-ui-header--subtitle">Learn how to create packages in the <a href="https://cerb.ai/docs/packages/" target="_blank">documentation</a>.</div>
			</div>
		</div>
	</div>
</div>
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">{'common.package'|devblocks_translate|capitalize} <span class="cerb-u-text-muted cerb-u-fw-400">(JSON)</span></div>
		<div class="cerb-ui-header--right">{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/packages/"}</div>
	</div>

	<textarea id="packageJsonEditor_{$form_id}" name="package_json" data-editor-lines="25" spellcheck="false">{if $model}{$model->getPackageJson()}{else}{literal}{
  "package": {
    "name": "Package Name",
    "revision": 1,
    "requires": {
      "cerb_version": "{/literal}{$smarty.const.APP_VERSION}{literal}",
      "plugins": [
      ]
    },
    "library": {
      "name": "",
      "uri": "",
      "description": "",
      "point": "",
      "image": "data:image/png;base64,"
    },
    "configure": {
      "placeholders": [
      ],
      "prompts": [
      ]
    }
  },
  "records": [
  ]
}{/literal}{/if}</textarea>
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
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="package"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.package'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Form elements
		new CerbUI.JsonEditor($popup.find('#packageJsonEditor_{$form_id}')[0], { validate: true, minLines: 8 });

	});
});
</script>
