<form id="frmDecisionSubroutine{$id}" onsubmit="return false;" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="saveDecisionPopup">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Subroutines are reusable sections of behavior</legend>
	<b>Subroutines</b> are reusable sections of a larger behavior.
</fieldset>

<b>{'common.title'|devblocks_translate|capitalize}:</b>
<div style="margin:0px 0px 10px 10px;">
	<input type="text" name="title" value="{$model->title}" style="width:100%;" autocomplete="off" spellcheck="false" placeholder="doSomething()">
</div>

<b>{'common.status'|devblocks_translate|capitalize}:</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
	<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
	<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
</div>

</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this subroutine?</legend>
	<p>Are you sure you want to permanently delete this subroutine and its children?</p>
	<button type="button" class="green" data-cerb-button="delete-confirm"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" data-cerb-button="delete-reject"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="toolbar">
	<button type="button" data-cerb-button="save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if isset($id)}<button type="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_subroutine{$id}');
	var $frm = $('#frmDecisionSubroutine{$id}');
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Subroutine");
		$popup.find('input:text').first().focus();

		$popup.find('[data-cerb-button=save]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_subroutine{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('.toolbar').hide().prev('fieldset.delete').show();
		});

		$popup.find('[data-cerb-button=delete-confirm]').on('click', function(e) {
			e.stopPropagation();

			var formData = new FormData($frm[0]);
			formData.set('action', 'saveDecisionDeletePopup');

			genericAjaxPost(formData,null,null,function() {
				genericAjaxPopupDestroy('node_subroutine{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete-reject]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('fieldset').hide().next('.toolbar').show();
		});
	});
});
</script>