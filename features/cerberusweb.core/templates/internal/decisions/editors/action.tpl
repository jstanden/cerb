<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#action{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#action{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	<div id="action{$id}-build" class="action-build">
		<form id="frmDecisionAction{$id}Action" onsubmit="return false;" method="post">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
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
		
		<div class="actions">
		
		{$seq = null}
		{if $model && isset($model->params.actions) && is_array($model->params.actions)}
		{foreach from=$model->params.actions item=params key=seq}
		<fieldset id="action{$seq}_{$nonce}" class="cerb-bot-action">
			<legend class="cerb-bot-action--title" style="cursor:move;">
				<a href="javascript:;" onclick="$(this).closest('fieldset').find('#divDecisionActionToolbar{$id}').hide().appendTo($('#frmDecisionAction{$id}Action'));$(this).closest('fieldset').trigger('cerb.remove');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
				{if $actions[$params.action]}
					{$actions[$params.action].label}
				{else}
					(missing action: {$params.action})
				{/if}
			</legend>
			
			<input type="hidden" name="actions[]" value="{$seq}">
			<input type="hidden" name="action{$seq}[action]" value="{$params.action}">
			
			{if $actions.{$params.action}}
				{$event->renderAction({$params.action},$trigger,$params,$seq)}
			{else}
				The defined action could not be found. It may no longer be supported, or its plugin may be disabled. 
				The action will be ignored by this behavior until it becomes available again.
			{/if}
		</fieldset>
		{/foreach}
		{/if}
		</div>
		
		<div id="divDecisionActionToolbar{$id}" style="display:none;">
			<div class="tester"></div>
		
			<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button>
			<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
			<button type="button" data-cerb-button="toolbar-help">Help</button>
			
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
		
		</form>
		
		<form id="frmDecisionActionAdd{$id}" action="javascript:;" onsubmit="return false;" method="post">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="getActionParams">
		<input type="hidden" name="action_uid" value="getActionParams">
		<input type="hidden" name="seq" value="{$model->params.actions|default:[]|count}">
		<input type="hidden" name="nonce" value="{$nonce}">
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<fieldset>
			<legend>{'common.actions'|devblocks_translate|capitalize}</legend>
		
			<button type="button" class="action cerb-popupmenu-trigger">{'common.action'|devblocks_translate|capitalize} &#x25be;</button>
		
			{function menu level=0}
				{foreach from=$keys item=data key=idx}
					{if is_array($data->children) && !empty($data->children)}
						<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
							{if $data->key}
								<div style="font-weight:bold;">{$data->l|capitalize}</div>
							{else}
								<div>{$idx|capitalize}</div>
							{/if}
							<ul>
								{menu keys=$data->children level=$level+1}
							</ul>
						</li>
					{elseif $data->key}
						<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
					{/if}
				{/foreach}
			{/function}
			
			<ul class="actions-menu" style="width:150px;display:none;">
			{menu keys=$actions_menu}
			</ul>
		
		</fieldset>
		</form>

		{if isset($id)}
		<fieldset class="delete" style="display:none;">
			<legend>Delete this action?</legend>
			<p>Are you sure you want to permanently delete this action?</p>
			<button type="button" class="green" data-cerb-button="delete-confirm"> {'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" class="red" data-cerb-button="delete-reject"> {'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="toolbar">
			{if !isset($id)}
				<button type="button" data-cerb-button="save-create"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{else}
				<button type="button" data-cerb-button="save-close"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
				<button type="button" data-cerb-button="save-continue"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
				<button type="button" data-cerb-button="simulate"> <span class="glyphicons glyphicons-cogwheel"></span> Simulator</button>
				<button type="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>
	
	{if !$id && $packages}
	<div id="action{$id}-library" class="package-library">
		<form id="frmDecisionAction{$id}Library" onsubmit="return false;">
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
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_action{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Actions");
		$popup.find('input:text').first().focus();
		$popup.css('overflow', 'inherit');
		
		var $toolbar = $('#divDecisionActionToolbar{$id}');
		var $frm_build = $('#frmDecisionAction{$id}Action');
		var $frm_library = $('#frmDecisionAction{$id}Library');
		var $frm_add_action = $('#frmDecisionActionAdd{$id}');

		$popup.find('[data-cerb-button=save-create]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_action{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=save-close]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_action{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=save-continue]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				Devblocks.createAlert('Saved!', 'note');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=simulate]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'50%');
		});

		$popup.find('[data-cerb-button=delete]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('.toolbar').hide().prev('fieldset.delete').show();
		});

		$popup.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');
		});

		$popup.find('[data-cerb-button=delete-confirm]').on('click', function(e) {
			e.stopPropagation();
			var formData = new FormData($frm_build[0]);
			formData.set('action', 'saveDecisionDeletePopup');

			genericAjaxPost(formData,null,null,function() {
				genericAjaxPopupDestroy('node_action{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete-reject]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('fieldset').hide().next('.toolbar').show();
		});

		// Make sure the toolbar is never removed
		$popup.on('cerb.remove', function(e) {
			e.stopPropagation();
			var $target = $(e.target);
			$toolbar.detach();
			$target.remove();
		});
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Package Library
		
		{if !$id && $packages}
			var $tabs = $popup.find('.cerb-tabs').tabs();
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				Devblocks.clearAlerts();
				
				genericAjaxPost($frm_library,null,null,function(json) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
					
					if(json.error) {
						Devblocks.createAlertError(json.error);
						
					} else if (json.id && json.type) {
						genericAjaxPopupDestroy('node_action{$id}');
						
						genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}', function() {
							genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
						});
					}
				});
				
			});
		{/if}
		
		// Choosers
		
		$popup.find('BUTTON.chooser_group.unbound').each(function() {
			var seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.group','action'+seq+'[group_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
		
		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			var seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
		$popup.find('BUTTON.chooser_notify_workers.unbound').each(function() {
			var seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[notify_worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		$popup.find('#frmDecisionAction{$id}Action DIV.actions')
			.sortable({ 'items':'fieldset.cerb-bot-action', 'placeholder':'ui-state-highlight', 'handle':'legend.cerb-bot-action--title' })
		;

		// Placeholders
		
		$popup.find('textarea.placeholders, :text.placeholders').each(function() {
			var $this = $(this);
			
			$this.cerbCodeEditor();
			
			if('ace/mode/cerb_query' == $this.attr('data-editor-mode'))
				$this.cerbCodeEditorAutocompleteDataQueries();
		});
		
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
				if(0 == $target.nextAll('#divDecisionActionToolbar{$id}').length) {
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
			
			var $field = null;
			
			
			if($toolbar.data('src')) {
				$field = $toolbar.data('src');
			} else {
				$field = $toolbar.prev(':text, textarea');
			}
			
			if(null == $field)
				return;
			
			if($field.is('.ace_editor')) {
				var $field = $field.prev('textarea, :text');
			}
			
			var regexpName = /^(.*?)(\[.*?\])$/;
			var hits = regexpName.exec($field.attr('name'));
			
			if(null == hits || hits.length < 3)
				return;
			
			var strNamespace = hits[1];
			var strName = hits[2];

			var formData = new FormData($frm_build[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'behavior');
			formData.set('action', 'testDecisionEventSnippets');
			formData.set('prefix', strNamespace);
			formData.set('field', strName);

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
		
		// Action menu
		
		var $actions_menu_trigger = $frm_add_action.find('button.action.cerb-popupmenu-trigger');
		var $actions_menu = $frm_add_action.find('ul.actions-menu');

		$actions_menu_trigger.click(function() {
			$actions_menu.toggle();
		});
		
		$actions_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label').replace('(Common) ','');
				
				if(undefined == token || undefined == label)
					return;
				
				$frm_add_action.find('input[name=action_uid]').val(token);

				genericAjaxPost($frm_add_action,null,null,function(html) {
					var $ul = $('#frmDecisionAction{$id}Action DIV.actions');
					
					var seq = parseInt($frm_add_action.find('input[name=seq]').val());
					if(null == seq)
						seq = 0;
					
					var $container = $('<fieldset/>').attr('id','action' + seq + '_{$nonce}').addClass('cerb-bot-action');
					$container.prepend('<legend class="cerb-bot-action--title" style="cursor:move;"><a href="javascript:;" onclick="$(this).closest(\'fieldset\').find(\'#divDecisionActionToolbar{$id}\').hide().appendTo($(\'#frmDecisionAction{$id}Action\'));$(this).closest(\'fieldset\').trigger(\'cerb.remove\');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a> ' + label + '</legend>');
					$container.append('<input type="hidden" name="actions[]" value="' + seq + '">');
					$container.append('<input type="hidden" name="action'+seq+'[action]" value="' + token + '">');
					$ul.append($container);
					
					var $html = $('<div/>').html(html);
					$container.append($html);
					
					$html.find('BUTTON.chooser_group.unbound').each(function() {
						ajax.chooser(this,'cerberusweb.contexts.group','action'+seq+'[group_id]', { autocomplete:true });
						$(this).removeClass('unbound');
					});
					
					$html.find('BUTTON.chooser_worker.unbound').each(function() {
						ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
						$(this).removeClass('unbound');
					});
					$html.find('BUTTON.chooser_notify_workers.unbound').each(function() {
						ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[notify_worker_id]', { autocomplete:true });
						$(this).removeClass('unbound');
					});
					
					$html.find('textarea.placeholders, :text.placeholders').each(function() {
						var $this = $(this);
						
						$this.cerbCodeEditor();
						
						if('ace/mode/cerb_query' == $this.attr('data-editor-mode'))
							$this.cerbCodeEditorAutocompleteDataQueries();
					});
					
					$frm_add_action.find('input[name=seq]').val(1+seq);
				});
				
			}
		});
		
	}); // popup_open
	
});
</script>