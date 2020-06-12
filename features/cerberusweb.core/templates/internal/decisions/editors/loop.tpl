<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#loop{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#loop{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$id && $packages}
	<div id="loop{$id}-library" class="package-library">
		<form id="frmDecisionLoop{$id}Library" onsubmit="return false;" method="post">
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
	
	<div id="loop{$id}-build">
		<form id="frmDecisionLoop{$id}" onsubmit="return false;">
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
				<legend>Repeat this branch for each object in a list</legend>
				A <b>loop</b> branch will repeat its decisions and actions for each object in a list.
			</fieldset>
			
			<b>{'common.title'|devblocks_translate|capitalize}:</b>
			<div style="margin:0px 0px 10px 10px;">
				<input type="text" name="title" value="{$model->title}" style="width:100%;" autofocus="autofocus" autocomplete="off" spellcheck="false">
			</div>
			
			<b>{'common.status'|devblocks_translate|capitalize}:</b>
			<div style="margin:0px 0px 10px 10px;">
				<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
				<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
				<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
			</div>
			
			<b>For each object in this JSON array:</b>
			<div style="margin:0px 0px 10px 10px;">
				<textarea name="params[foreach_json]" data-editor-mode="ace/mode/twig" class="placeholders" style="width:100%;height:200px;">{$model->params.foreach_json}</textarea>
			</div>
			
			<div id="divDecisionLoopToolbar{$id}" style="display:none;">
				<div class="tester"></div>
			
				<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
				<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
				<button type="button" onclick="genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
				
				{$types = $values._types}
				{function tree level=0}
					{foreach from=$keys item=data key=idx}
						{$type = $types.{$data->key}}
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
								{if $data->key}
									<div style="font-weight:bold;">{$data->l|capitalize}</div>
								{else}
									<div>{$idx|capitalize}</div>
								{/if}
								<ul>
									{tree keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
							<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
						{/if}
					{/foreach}
				{/function}
				
				<ul class="menu" style="width:150px;">
				{tree keys=$placeholders}
				</ul>
			</div>
			
			<b>Set this object placeholder:</b>
			<div style="margin:0px 0px 10px 10px;">
				{literal}{{{/literal}<input type="text" name="params[as_placeholder]" value="{$model->params.as_placeholder}" size="32">{literal}}}{/literal}
			</div>
		</form>
		
		{if isset($id)}
		<fieldset class="delete" style="display:none;">
			<legend>Delete this loop?</legend>
			<p>Are you sure you want to permanently delete this loop and its children?</p>
			<button type="button" class="green" data-cerb-button="delete-confirm"> {'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" class="red" data-cerb-button="delete-reject"> {'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="toolbar">
			<button type="button" data-cerb-button="save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if isset($id)}<button type="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_loop{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Loop");
		$popup.css('overflow', 'inherit');

		var $frm_build = $('#frmDecisionLoop{$id}');
		var $frm_library = $('#frmDecisionLoop{$id}Library');
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});

		$popup.find('[data-cerb-button=save]').on('click', function(e) {
			e.stopPropagation();

			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_loop{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete]').on('click', function(e) {
			e.stopPropagation();

			$(this).closest('.toolbar').hide().prev('fieldset.delete').show();
		});

		$popup.find('[data-cerb-button=delete-confirm]').on('click', function(e) {
			e.stopPropagation();

			var formData = new FormData($frm_build[0]);
			formData.set('action','saveDecisionDeletePopup');

			genericAjaxPost(formData,null,null,function() {
				genericAjaxPopupDestroy('node_loop{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete-reject]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('fieldset').hide().next('.toolbar').show();
		});

		// Package Library
		
		{if !$id && $packages}
			var $library_container = $popup.find('.cerb-tabs').tabs();
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				Devblocks.clearAlerts();
				
				genericAjaxPost($frm_library,null,null, function(json) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
					
					if(json.error) {
						Devblocks.createAlertError(json.error);
						
					} else if (json.id && json.type) {
						genericAjaxPopupDestroy('node_loop{$id}');
						
						genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}', function() {
							genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
						});
					}
				});
			});
		{/if}
		
		// Placeholder toolbar
		
		var $toolbar = $('#divDecisionLoopToolbar{$id}');
		
		$popup.find('textarea.placeholders, :text.placeholders').cerbCodeEditor();
		
		$popup.delegate(':text.placeholders, textarea.placeholders, pre.placeholders', 'focus', function(e) {
			e.stopPropagation();
			
			var $target = $(e.target);
			var $parent = $target.closest('.ace_editor');
			
			if(0 != $parent.length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				$toolbar.show().insertAfter($parent);
				$toolbar.data('src', $parent);
				
			} else {
				if(0 == $target.nextAll('#divDecisionLoopToolbar{$id}').length) {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter($target);
					$toolbar.data('src', $target);
					$toolbar.find('button.tester').show();
				}
			}
		});
		
		// Placeholder menu
		
		var $placeholder_menu_trigger = $toolbar.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $toolbar.find('ul.menu').hide();
		
		// Quick insert token menu
		
		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				var $field = null;
				
				if($toolbar.data('src')) {
					$field = $toolbar.data('src');
				
				} else {
					$field = $toolbar.prev(':text, textarea');
				}
				
				if(null == $field)
					return;
				
				if(null == $field)
					return;
				
				if($field.is(':text, textarea')) {
					$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
					
				} else if($field.is('.ace_editor')) {
					var evt = new jQuery.Event('cerb.insertAtCursor');
					evt.content = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
					$field.trigger(evt);
				}
			}
		});
		
		$toolbar.find('button.tester').click(function(e) {
			var divTester = $toolbar.find('div.tester').first();
			
			var formData = new FormData($frm_build[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'behavior');
			formData.set('action', 'testDecisionEventSnippets');
			formData.set('prefix', 'params');
			formData.set('field', 'foreach_json');

			genericAjaxPost(formData, divTester, null);
		});
		
		$placeholder_menu_trigger
			.click(
				function(e) {
					$placeholder_menu.toggle();
				}
			)
			.bind('remove',
				function(e) {
					$placeholder_menu.remove();
				}
			)
		;
	});
});
</script>