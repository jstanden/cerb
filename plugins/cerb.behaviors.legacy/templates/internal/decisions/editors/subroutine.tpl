<form id="frmDecisionSubroutine{$id}" method="post" class="cerb-ui-form">
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
				<div class="cerb-ui-header--title-sm">Subroutines are reusable sections of behavior</div>
				<div class="cerb-ui-header--subtitle"><b>Subroutines</b> are reusable sections of a larger behavior.</div>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
	<input type="text" name="title" value="{$model->title}" autocomplete="off" spellcheck="false" placeholder="doSomething()">
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

</form>

{if isset($id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="subroutine"}
{/if}

<div class="buttons cerb-u-mt-3">
	<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('node_subroutine{$id}');
	let $frm = $('#frmDecisionSubroutine{$id}');

	Devblocks.formDisableSubmit($frm);

	// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Subroutine");
		$popup.find('input:text').first().focus();

		if(window.CerbUI && CerbUI.Switcher) {
			$frm.find('.cerb-behavior-status-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[name=status_id]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$popup.find('[data-cerb-button=save]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_subroutine{$id}');
				refreshTrees();
			});
		});

		if(window.CerbUI && CerbUI.Form) {
			CerbUI.Form.ConfirmDelete($popup[0], { onConfirm: function() {
				var formData = new FormData($frm[0]);
				formData.set('action', 'saveDecisionDeletePopup');
				genericAjaxPost(formData,null,null,function() {
					genericAjaxPopupDestroy('node_subroutine{$id}');
					refreshTrees();
				});
			}});
		}
	});
});
</script>
