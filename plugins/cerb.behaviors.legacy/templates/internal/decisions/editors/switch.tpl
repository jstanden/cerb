<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#switch{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#switch{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}

	{if !$id && $packages}
	<div id="switch{$id}-library" class="package-library">
		<form id="frmDecisionSwitch{$id}Library">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
		</form>
	</div>
	{/if}

	<div id="switch{$id}-build">
		<form id="frmDecisionSwitch{$id}" method="post" class="cerb-ui-form">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
			<div class="cerb-ui-header">
				<div class="cerb-ui-callout">
					<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
					<div>
						<div class="cerb-ui-header--title-sm">Determine an outcome based on multiple choices</div>
						<div class="cerb-ui-header--subtitle">
							A <b>decision</b> will evaluate multiple choices and choose the first outcome that satisfies all conditions.
							Each outcome may use different conditions. For example, you can use a decision to choose from a list: language,
							time of day, day of week, service level, etc.
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
			<input type="text" name="title" value="{$model->title}" autocomplete="off" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="status_id" value="{$model->status_id|default:0}">
			<div>
				<div class="cerb-ui-switcher cerb-behavior-status-switcher">
					<button type="button" data-value="0"{if !$model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-play-button"></span> Live</button>
					<button type="button" data-value="2"{if 2 == $model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lab"></span> Simulator only</button>
					<button type="button" data-value="1"{if 1 == $model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> Disabled</button>
				</div>
			</div>
		</div>

		{if $id}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="decision"}
		{/if}

		<div class="buttons">
			<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if isset($id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
		</form>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('node_switch{$id}');

	Devblocks.formDisableSubmit($popup.find('form'));

	// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Decision");
		$popup.find('input:text').first().focus();

		let $frm = $popup.find('#frmDecisionSwitch{$id}');

		if(window.CerbUI && CerbUI.Switcher) {
			$frm.find('.cerb-behavior-status-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[name=status_id]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$frm.find('[data-cerb-button=save]').on('click', function() {
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_switch{$id}');
				refreshTrees();
			});
		});

		if(window.CerbUI && CerbUI.Form) {
			CerbUI.Form.ConfirmDelete($popup[0], { onConfirm: function() {
				var formData = new FormData($frm[0]);
				formData.set('action', 'saveDecisionDeletePopup');
				genericAjaxPost(formData,null,null,function() {
					genericAjaxPopupDestroy('node_switch{$id}');
					refreshTrees();
				});
			}});
		}

		// Package Library

		{if !$id && $packages}
			var $library_container = $popup.find('.cerb-tabs');
			$library_container.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}

			$library_container.on('cerb-package-library-form-submit', function(e) {
				e.stopPropagation();
				Devblocks.clearAlerts();

				genericAjaxPost('frmDecisionSwitch{$id}Library',null,null, function(json) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');

					if(json.error) {
						Devblocks.createAlertError(json.error);

					} else if (json.id && json.type) {
						genericAjaxPopupDestroy('node_switch{$id}');

						refreshTrees();
						genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
					}
				});
			});
		{/if}
	});
});
</script>
