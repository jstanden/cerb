<div class="cerb-tabs">
	{if !$id && $packages}
	<ul>
		<li><a href="#action{$id}-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#action{$id}-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}

	<div id="action{$id}-build" class="action-build">
		<form id="frmDecisionAction{$id}Action" method="post" class="cerb-ui-form">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="saveDecisionPopup">
		{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
		{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
		{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
			<input type="text" name="title" value="{$model->title|default:''}" autofocus="autofocus" autocomplete="off" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="status_id" value="{$model->status_id|default:0}">
			<div>
				<div class="cerb-ui-switcher cerb-behavior-status-switcher">
					<button type="button" data-value="0"{if !$model || !$model->status_id} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-play-button"></span> Live</button>
					<button type="button" data-value="2"{if 2 == $model->status_id|default:0} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lab"></span> Simulator only</button>
					<button type="button" data-value="1"{if 1 == $model->status_id|default:0} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> Disabled</button>
				</div>
			</div>
		</div>

		<div class="actions">

		{$seq = null}
		{if $model && array_key_exists('actions', $model->params) && is_array($model->params.actions)}
		{foreach from=$model->params.actions item=params key=seq}
		<div id="action{$seq}_{$nonce}" class="cerb-ui-panel cerb-ui-panel--spaced cerb-bot-action">
			{$action = $params.action|default:''}
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center cerb-bot-action--title" style="cursor:move;">
				<div class="cerb-ui-header--title-sm">{if array_key_exists($action, $actions)}{$actions[$action].label}{else}(missing action: {$action}){/if}</div>
				<div class="cerb-ui-header--right">
					<span data-cerb-onhover class="cerb-icons cerb-icon-circle-remove" style="display:none;cursor:pointer;"></span>
				</div>
			</div>

			<div class="cerb-ui-form cerb-bot-action--body">
				<input type="hidden" name="actions[]" value="{$seq}">
				<input type="hidden" name="action{$seq}[action]" value="{$action}">

				{if array_key_exists($action, $actions)}
					{$event->renderAction($action,$trigger,$params,$seq)}
				{else}
					The defined action could not be found. It may no longer be supported, or its plugin may be disabled.
					The action will be ignored by this behavior until it becomes available again.
				{/if}
			</div>
		</div>
		{/foreach}
		{/if}
		</div>

		<div id="divDecisionActionToolbar{$id}" style="display:none;">
			<div class="tester"></div>

			<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-popupmenu-trigger">Insert placeholder <span class="cerb-icons cerb-icon-chevron-down"></span></button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle tester">{'common.test'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="toolbar-help">Help</button>

			{$types = $values['_types']|default:[]}
			{function tree level=0}
				{foreach from=$keys item=data key=idx}
					{$type = $types[$data->key|default:'']}
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

		<form id="frmDecisionActionAdd{$id}" action="#" method="post">
		<input type="hidden" name="c" value="profiles">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="behavior">
		<input type="hidden" name="action" value="getActionParams">
		<input type="hidden" name="action_uid" value="getActionParams">
		<input type="hidden" name="seq" value="{$model->params.actions|default:[]|count}">
		<input type="hidden" name="nonce" value="{$nonce}">
		{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		<div class="cerb-u-my-2">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle action cerb-popupmenu-trigger"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.add'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></button>

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

			<ul class="actions-menu" style="width:150px;">
			{menu keys=$actions_menu}
			</ul>
		</div>
		</form>

		{if isset($id)}
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="action"}
		{/if}

		<div class="buttons">
			{if !isset($id)}
				<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save-create"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{else}
				<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save-close"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="simulate"><span class="cerb-icons cerb-icon-gear"></span> Simulator</button>
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>
			{/if}
		</div>
	</div>

	{if !$id && $packages}
	<div id="action{$id}-library" class="package-library">
		<form id="frmDecisionAction{$id}Library">
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('node_action{$id}');

	Devblocks.formDisableSubmit($popup.find('form'));

	// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Actions");
		$popup.find('input:text').first().focus();
		$popup.css('overflow', 'inherit');

		var $toolbar = $('#divDecisionActionToolbar{$id}');
		var $frm_build = $('#frmDecisionAction{$id}Action');
		var $frm_library = $('#frmDecisionAction{$id}Library');
		var $frm_add_action = $('#frmDecisionActionAdd{$id}');

		if(window.CerbUI && CerbUI.Switcher) {
			$frm_build.find('.cerb-behavior-status-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[name=status_id]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$popup.find('[data-cerb-button=save-create]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_action{$id}');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=save-close]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				genericAjaxPopupDestroy('node_action{$id}');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=save-continue]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm_build,null,null,function() {
				Devblocks.createAlert('Saved!', 'note');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=simulate]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'50%');
		});

		if(window.CerbUI && CerbUI.Form) {
			CerbUI.Form.ConfirmDelete($popup[0], { onConfirm: function() {
				var formData = new FormData($frm_build[0]);
				formData.set('action', 'saveDecisionDeletePopup');
				genericAjaxPost(formData,null,null,function() {
					genericAjaxPopupDestroy('node_action{$id}');
					refreshTrees();
				});
			}});
		}

		$popup.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');
		});

		// Make sure the toolbar is never removed
		$popup.on('cerb.remove', function(e) {
			e.stopPropagation();
			var $target = $(e.target);
			$toolbar.detach();
			$target.remove();
		});

		// Build

		let funcBehaviorActionRemove = function(e) {
			e.stopPropagation();
			let id = $(this).attr('data-cerb-node-id');
			$(this).closest('.cerb-bot-action').find('#divDecisionActionToolbar' + id).hide().appendTo($('#frmDecisionAction' + id + 'Action'));
			$(this).closest('.cerb-bot-action').trigger('cerb.remove');
		}

		$frm_build.find('.cerb-bot-action .cerb-ui-header .cerb-icon-circle-remove').on('click', funcBehaviorActionRemove);

		// Package Library

		{if !$id && $packages}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
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

						refreshTrees();
						genericAjaxPopup('node_' + json.type + json.id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(json.id),null,false,'50%');
					}
				});

			});
		{/if}

		// Choosers

		$popup.find('.chooser_group.unbound').each(function() {
			var seq = $(this).closest('.cerb-bot-action').find('input:hidden[name="actions[]"]').val();
			if(window.CerbUI && CerbUI.RecordChooser)
				new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.group', name:'action'+seq+'[group_id]', multiple:true, emptyIcon:'users' });
			$(this).removeClass('unbound');
		});

		$popup.find('.chooser_worker.unbound').each(function() {
			var seq = $(this).closest('.cerb-bot-action').find('input:hidden[name="actions[]"]').val();
			if(window.CerbUI && CerbUI.RecordChooser)
				new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.worker', name:'action'+seq+'[worker_id]', multiple:true, emptyIcon:'user' });
			$(this).removeClass('unbound');
		});

		if(window.CerbUI && CerbUI.Sortable)
			new CerbUI.Sortable($popup.find('#frmDecisionAction{$id}Action div.actions').get(0), {
				items: '.cerb-bot-action',
				handle: '.cerb-ui-header',
				tolerance: 'pointer'
			});

		$popup.find('#frmDecisionAction{$id}Action DIV.actions').on({
			mouseenter: function() {
				$(this).find(':hidden[data-cerb-onhover]').show();
			},
			mouseleave: function() {
				$(this).find(':visible[data-cerb-onhover]').hide();
			}
		}, ".cerb-bot-action");

		// Placeholders

		// Each .placeholders field becomes a CerbUI.ScriptingEditor (a <textarea> is multi-line; an <input> is
		// single-line/wrap with a guaranteed single-line value via enhance()). The shared placeholder toolbar docks
		// onto the editor on focus; token insertion + the tester resolve the editor instance (see the menu below).
		// Runs at init AND on each dynamically-loaded action template.
		var enhancePlaceholders = function($scope) {
			if(!(window.CerbUI && CerbUI.ScriptingEditor)) return;
			$scope.find('textarea.placeholders, :text.placeholders').each(function() {
				var isInput = this.tagName === 'INPUT';
				this.classList.remove('placeholders');   // the carrier stays named + submitted, just not re-matched
				var ed = CerbUI.ScriptingEditor.enhance(this, {
					singleLine: isInput,
					minLines: isInput ? 1 : 3,
					maxLines: isInput ? 6 : 12
				});
				if(!ed) return;
				ed.textarea.addEventListener('focus', function() {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter(ed.el);
					$toolbar.data('src', ed);
					$toolbar.find('button.tester').show();
				});
			});
		};

		enhancePlaceholders($popup);

		// Late-added fields (e.g. an AJAX-added custom fieldset) request enhancement via this event
		$popup.on('cerb-placeholders--enhance', function(e) {
			enhancePlaceholders($(e.target));
		});

		// Placeholder menu

		var $placeholder_menu_trigger = $toolbar.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $toolbar.find('ul.menu').hide();

		// Quick insert token menu

		var menu = new CerbUI.Menu($placeholder_menu[0], {
			clickTrigger: $placeholder_menu_trigger[0],
			selectableParents: true,
			filter: true,
			onSelect: function(li, src) {
				var token = src.getAttribute('data-token');
				var label = src.getAttribute('data-label');

				if(undefined == token || undefined == label)
					return;

				var insert = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
				var fieldSrc = $toolbar.data('src');

				// The docked source is a ScriptingEditor (enhanced field) or a plain jQuery field (any stray case).
				if(fieldSrc instanceof CerbUI.ScriptingEditor) {
					fieldSrc.focus();
					fieldSrc.insertAtCursor(insert);
					return;
				}

				var $field = fieldSrc ? $(fieldSrc) : $toolbar.prev(':text, textarea');

				if(!$field || 0 == $field.length)
					return;

				if($field.is(':text, textarea'))
					$field.focus().insertAtCursor(insert);
			}
		});

		$toolbar.find('button.tester').click(function(e) {
			var divTester = $toolbar.find('div.tester').first();

			var fieldSrc = $toolbar.data('src');
			var $field = null;

			// Resolve the named control: for an enhanced editor it's the value carrier (input) or the textarea itself.
			if(fieldSrc instanceof CerbUI.ScriptingEditor)
				$field = $(fieldSrc.el).find('[name]').first();
			else if(fieldSrc)
				$field = $(fieldSrc);
			else
				$field = $toolbar.prev(':text, textarea');

			if(null == $field || 0 == $field.length)
				return;

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
			.bind('remove',
				function(e) {
					if(menu)
						menu.destroy();
					$placeholder_menu.remove();
				}
			)
		;

		// Action menu

		var $actions_menu_trigger = $frm_add_action.find('button.action.cerb-popupmenu-trigger');
		var $actions_menu = $frm_add_action.find('ul.actions-menu').hide();

		new CerbUI.Menu($actions_menu[0], {
			clickTrigger: $actions_menu_trigger[0],
			selectableParents: true,
			filter: true,
			onSelect: function(li, src) {
				var token = src.getAttribute('data-token');
				var label = src.getAttribute('data-label');

				if(label)
					label = label.replace('(Common) ','');

				if(undefined == token || undefined == label)
					return;

				$frm_add_action.find('input[name=action_uid]').val(token);

				genericAjaxPost($frm_add_action,null,null,function(html) {
					var $ul = $('#frmDecisionAction{$id}Action DIV.actions');

					var seq = parseInt($frm_add_action.find('input[name=seq]').val());
					if(null == seq)
						seq = 0;

					var $container = $('<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-bot-action" />').attr('id','action' + seq + '_{$nonce}');
					$container.prepend('<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center cerb-bot-action--title" style="cursor:move;">'
						+ '<div class="cerb-ui-header--title-sm"></div>'
						+ '<div class="cerb-ui-header--right"><span data-cerb-onhover class="cerb-icons cerb-icon-circle-remove" style="display:none;cursor:pointer;"></span></div>'
						+ '</div>'
					);
					$container.find('.cerb-ui-header--title-sm').text(label);
					$container.find('.cerb-ui-header .cerb-icon-circle-remove').on('click', funcBehaviorActionRemove);
					var $div = $('<div class="cerb-ui-form cerb-bot-action--body" />').appendTo($container);
					$ul.append($container);

					var $html = $div.html(html);
					$div.prepend('<input type="hidden" name="action'+seq+'[action]" value="' + token + '">');
					$div.prepend('<input type="hidden" name="actions[]" value="' + seq + '">');

					$container.append($html);

					$html.find('.chooser_group.unbound').each(function() {
						if(window.CerbUI && CerbUI.RecordChooser)
							new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.group', name:'action'+seq+'[group_id]', multiple:true, emptyIcon:'users' });
						$(this).removeClass('unbound');
					});

					$html.find('.chooser_worker.unbound').each(function() {
						if(window.CerbUI && CerbUI.RecordChooser)
							new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.worker', name:'action'+seq+'[worker_id]', multiple:true, emptyIcon:'user' });
						$(this).removeClass('unbound');
					});

					enhancePlaceholders($html);

					$frm_add_action.find('input[name=seq]').val(1+seq);
				});

			}
		});

	}); // popup_open

});
</script>
