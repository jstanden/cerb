{$peek_context = CerberusContexts::CONTEXT_PACKAGE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="package">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
<div class="help-box">
	<h1>Building packages</h1>
	Learn how to create packages in the <a href="https://cerb.ai/docs/packages/" target="_blank">documentation</a>.
</div>
{/if}

<div>
	<b>{'common.package'|devblocks_translate|capitalize}:</b> (JSON) {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/packages/"}
	<textarea name="package_json" class="cerb-code-editor" data-editor-mode="ace/mode/json">{if $model}{$model->getPackageJson()}{else}{literal}{
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
{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this package?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}


<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.package'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Form elements
		$popup.find('.cerb-code-editor')
			.cerbCodeEditor()
			;
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
