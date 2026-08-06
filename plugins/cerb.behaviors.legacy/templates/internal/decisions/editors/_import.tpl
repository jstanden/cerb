<form action="#" method="post" id="frmBehaviorImport" class="cerb-ui-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="saveImportPopupJson">
<input type="hidden" name="trigger_id" value="{$trigger->id}">
<input type="hidden" name="node_id" value="{$node_id}">

<div class="cerb-ui-chip">
	<div class="cerb-ui-chip--head">Behavior</div>
	<div><div class="cerb-ui-chip--value">{$trigger->title}</div></div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Behavior JSON</label>
	{* JsonEditor keeps the named textarea as a synced value carrier, so a folded submit still posts the full document *}
	<textarea name="behavior_json" data-editor-lines="16" spellcheck="false" placeholder="Paste the behavior JSON here."></textarea>
</div>

<div class="config"></div>

<div>
	<button class="cerb-ui-button" type="button" data-cerb-button="import"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmBehaviorImport');
	let $popup = genericAjaxPopupFind($frm);
	let $config = $popup.find('div.config');

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'Import Behavior Fragment'}");

		if(window.CerbUI && CerbUI.JsonEditor) {
			const elEditor = $frm.find('textarea[name=behavior_json]').get(0);
			if(elEditor)
				new CerbUI.JsonEditor(elEditor, { validate: true });
		}

		$frm.find('[data-cerb-button=import]').click(function(e) {
			Devblocks.clearAlerts();

			genericAjaxPost($frm,'','',function(json) {
				if(json && json.config_html) {
					$config.hide().html(json.config_html).fadeIn();
					return;
				}

				if(!json || !json.status) {
					Devblocks.createAlertError(json.error);
					return;
				}

				if(json.status) {
					// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
					$('[data-behavior-tree-id="{$trigger->id}"]').each(function() {
						genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger->id}&tree_dom_id=' + encodeURIComponent(this.id));
					});
					$popup.dialog('close');
					return;
				}
			});
		});
	});
});
</script>
