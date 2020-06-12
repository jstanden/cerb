<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#switch{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#switch{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$id && $packages}
	<div id="switch{$id}-library" class="package-library">
		<form id="frmDecisionSwitch{$id}Library" onsubmit="return false;">
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
		<form id="frmDecisionSwitch{$id}" onsubmit="return false;" method="post">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<fieldset class="block black">
			<legend>Determine an outcome based on multiple choices</legend>
			
			A <b>decision</b> will evaluate multiple choices and choose the first outcome that satisfies all conditions. 
			Each outcome may use different conditions.  For example, you can use a decision to choose from a list: language, 
			time of day, day of week, service level, etc.
		</fieldset>
		
		<b>{'common.title'|devblocks_translate|capitalize}:</b>
		<div style="margin:0px 0px 10px 10px;">
			<input type="text" name="title" value="{$model->title}" style="width:100%;" autocomplete="off" spellcheck="false">
		</div>
		
		<b>{'common.status'|devblocks_translate|capitalize}:</b>
		<div style="margin:0px 0px 10px 10px;">
			<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
			<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
			<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
		</div>

		{if $id}
		<fieldset class="delete" style="display:none;">
			<legend>Delete this decision?</legend>
			<p>Are you sure you want to permanently delete this decision and its children?</p>
			<button type="button" class="green" data-cerb-button="delete-confirm"> {'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" class="red" data-cerb-button="delete-reject"> {'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="toolbar">
			<button type="button" data-cerb-button="save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if isset($id)}<button type="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
		</form>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_switch{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Decision");
		$popup.find('input:text').first().focus();

		var $frm = $popup.find('#frmDecisionSwitch{$id}');

		$frm.find('[data-cerb-button=save]').on('click', function() {
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_switch{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});
		
		$frm.find('[data-cerb-button=delete]').on('click', function() {
			$(this).closest('.toolbar').hide().prev('fieldset.delete').show();
		});

		$frm.find('[data-cerb-button=delete-confirm]').on('click', function() {
			var formData = new FormData($frm[0]);
			formData.set('action', 'saveDecisionDeletePopup');

			genericAjaxPost(formData,null,null,function() {
				genericAjaxPopupDestroy('node_switch{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$frm.find('[data-cerb-button=delete-reject]').on('click', function() {
			$(this).closest('fieldset').hide().next('.toolbar').show();
		});

		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Package Library
		
		{if !$id && $packages}
			var $library_container = $popup.find('.cerb-tabs').tabs();
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
						
						genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}', function() {
							genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
						});
					}
				});
			});
		{/if}
	});
});
</script>