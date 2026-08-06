<form id="frmDecisionOutcome{$id}" method="post" class="cerb-ui-form">
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

{$seq = 0}

{if empty($model->params.groups)}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-outcome-group">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">If <a data-cerb-link="toggle_any">all&#x25be;</a> of these conditions are satisfied</div>
			<div class="cerb-ui-header--right">
				<a data-cerb-link="remove_conditions"><span class="cerb-icons cerb-icon-circle-minus"></span></a>
			</div>
		</div>
		<input type="hidden" name="nodes[]" value="all">

		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;"></ul>
	</div>

{else}
	{foreach from=$model->params.groups item=group_data}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-outcome-group">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">If <a data-cerb-link="toggle_any">{if !empty($group_data.any)}any{else}all{/if}&#x25be;</a> of these conditions are satisfied</div>
			<div class="cerb-ui-header--right">
				<a data-cerb-link="remove_conditions_set"><span class="cerb-icons cerb-icon-circle-minus"></span></a>
			</div>
		</div>
		<input type="hidden" name="nodes[]" value="{if !empty($group_data.any)}any{else}all{/if}">

		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;">
			{if isset($group_data.conditions) && is_array($group_data.conditions)}
			{foreach from=$group_data.conditions item=params}
				<li style="padding-bottom:5px;" id="condition{$seq}_{$nonce}">
					<input type="hidden" name="nodes[]" value="{$seq}">
					<input type="hidden" name="condition{$seq}[condition]" value="{$params.condition}">
					<a data-cerb-link="condition_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></a>
					<b style="cursor:move;">{$conditions.{$params.condition}.label}</b>&nbsp;
					<div style="margin-left:20px;">
						{$event->renderCondition({$params.condition},$trigger,$params,$seq)}
					</div>
				</li>
				{$seq = $seq + 1}
			{/foreach}
			{/if}
		</ul>
	</div>
	{/foreach}
{/if}

<div id="divDecisionOutcomeToolbar{$id}" style="display:none;">
	<div class="tester"></div>

	<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-popupmenu-trigger">Insert placeholder <span class="cerb-icons cerb-icon-chevron-down"></span></button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle tester">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="toolbar-help">Help</button>

	{$types = $values._types}
	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{$type = $types.{$data->key|default:''}}
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

<form id="frmDecisionOutcomeAdd{$id}" action="#" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="getConditionParams">
<input type="hidden" name="seq" value="{$seq}">
<input type="hidden" name="condition" value="">
<input type="hidden" name="nonce" value="{$nonce}">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.conditions'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle condition cerb-popupmenu-trigger">{'common.condition'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle group"><span class="cerb-icons cerb-icon-circle-plus"></span> Add Group</button>
	</div>

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

	<ul class="conditions-menu" style="width:150px;display:none;">
	{menu keys=$conditions_menu}
	</ul>
</div>
</form>

{if isset($id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="outcome"}
{/if}

<div class="buttons">
	{if !isset($id)}
		<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save-create"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" class="cerb-ui-button cerb-u-anim-group" data-cerb-button="save-close"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="simulator"><span class="cerb-icons cerb-icon-gear"></span> Simulator</button>
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('node_outcome{$id}');

	Devblocks.formDisableSubmit($popup.find('form'));

	// Refresh every on-page instance of this behavior's tree (it can appear in multiple widgets/cards).
	const refreshTrees = function() {
		$('[data-behavior-tree-id="{$trigger_id}"]').each(function() {
			genericAjaxGet(this.id, 'c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}&tree_dom_id=' + encodeURIComponent(this.id));
		});
	};

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Outcome");
		$popup.find('input:text').first().focus();
		$popup.css('overflow', 'inherit');

		// Make sure the toolbar is never removed
		$popup.on('cerb.remove', function(e) {
			e.stopPropagation();
			var $target = $(e.target);
			$toolbar.detach();
			$target.remove();
		});

		var $frm = $popup.find('#frmDecisionOutcome{$id}');
		var $toolbar = $('DIV#divDecisionOutcomeToolbar{$id}');

		if(window.CerbUI && CerbUI.Switcher) {
			$frm.find('.cerb-behavior-status-switcher').each(function() {
				const input = this.closest('.cerb-ui-form--field').querySelector('input[name=status_id]');
				new CerbUI.Switcher(this, { value: input ? input.value : null, onSelect: function(v) { if(input) input.value = v; } });
			});
		}

		$frm.find('[data-cerb-link=remove_conditions]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('.cerb-outcome-group').remove();
		});

		let funcConditionsSetRemove = function(e) {
			e.stopPropagation();
			$(this).closest('.cerb-outcome-group').trigger('cerb.remove');
		};

		$frm.find('[data-cerb-link=remove_conditions_set]').on('click', funcConditionsSetRemove);

		let funcConditionRemove = function(e) {
			e.stopPropagation();
			$(this).closest('li').trigger('cerb.remove');
		};

		$frm.find('[data-cerb-link=condition_remove]').on('click', funcConditionRemove);

		$popup.find('[data-cerb-button=save-create]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_outcome{$id}');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=save-close]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_outcome{$id}');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=save-continue]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				Devblocks.createAlert('Saved!', 'note');
				refreshTrees();
			});
		});

		$popup.find('[data-cerb-button=simulator]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'50%');
		});

		if(window.CerbUI && CerbUI.Form) {
			CerbUI.Form.ConfirmDelete($popup[0], { onConfirm: function() {
				var formData = new FormData($frm[0]);
				formData.set('action', 'saveDecisionDeletePopup');
				genericAjaxPost(formData,null,null,function() {
					genericAjaxPopupDestroy('node_outcome{$id}');
					refreshTrees();
				});
			}});
		}

		$popup.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20' }, false, '600');
		});

		$frm.find('.cerb-outcome-group ul.rules').each(function() {
			if(window.CerbUI && CerbUI.Sortable && !CerbUI.Sortable.from(this))
				new CerbUI.Sortable(this, { items:'li', handle:'> b', connectWith:'#frmDecisionOutcome{$id} .cerb-outcome-group ul.rules' });
		});

		var $funcGroupAnyToggle = function(e) {
			var $any = $(this).closest('.cerb-outcome-group').find('input:hidden:first');

			if("any" === $any.val()) {
				$(this).html("all&#x25be;");
				$any.val('all');
			} else {
				$(this).html("any&#x25be;");
				$any.val('any');
			}
		}

		$frm.find('[data-cerb-link=toggle_any]').click($funcGroupAnyToggle);

		$popup.find('.chooser_worker.unbound').each(function() {
			var seq = $(this).closest('li').find('input:hidden[name="nodes[]"]').val();
			if(window.CerbUI && CerbUI.RecordChooser)
				new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.worker', name:'condition'+seq+'[worker_id]', multiple:true, emptyIcon:'user' });
			$(this).removeClass('unbound');
		});

		var $frmAdd = $popup.find('#frmDecisionOutcomeAdd{$id}');

		$frmAdd.find('button.group')
			.click(function(e) {
				e.stopPropagation();

				var $group = $('<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-outcome-group"></div>');
				$group.append('<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">'
					+ '<div class="cerb-ui-header--title-sm">If <a data-cerb-link="toggle_any">all&#x25be;</a> of these conditions are satisfied</div>'
					+ '<div class="cerb-ui-header--right"><a data-cerb-link="remove_conditions_set"><span class="cerb-icons cerb-icon-circle-minus"></span></a></div>'
					+ '</div>'
				);
				$group.append('<input type="hidden" name="nodes[]" value="all">');
				$group.append('<ul class="rules" style="margin:0px;list-style:none;padding:0px;padding-bottom:5px;"></ul>');
				$group.find('[data-cerb-link=toggle_any]').click($funcGroupAnyToggle);
				$group.find('[data-cerb-link=remove_conditions_set]').on('click', funcConditionsSetRemove);
				$frm.append($group);

				$frm.find('.cerb-outcome-group ul.rules').each(function() {
					if(window.CerbUI && CerbUI.Sortable && !CerbUI.Sortable.from(this))
						new CerbUI.Sortable(this, { items:'li', handle:'> b', connectWith:'#frmDecisionOutcome{$id} .cerb-outcome-group ul.rules' });
				});
			})
			;

		// Placeholders: enhance each .placeholders field into a CerbUI.ScriptingEditor (Twig template), docking $toolbar
		// onto the editor on focus; token insertion + the tester resolve the editor instance (see the menu below).
		// Runs at init AND on each dynamically-loaded condition template.
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

				if(null == token || null == label)
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

			var formData = new FormData($frm[0]);
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

		// Quick insert condition menu

		var $conditions_menu_trigger = $frmAdd.find('button.condition.cerb-popupmenu-trigger');
		var $conditions_menu = $frmAdd.find('ul.conditions-menu').hide();

		new CerbUI.Menu($conditions_menu[0], {
			clickTrigger: $conditions_menu_trigger[0],
			selectableParents: true,
			filter: true,
			onSelect: function(li, src) {
				var token = src.getAttribute('data-token');
				var label = src.getAttribute('data-label');

				if(null == token || null == label)
					return;

				var $frmDecAdd = $('#frmDecisionOutcomeAdd{$id}');
				$frmDecAdd.find('input[name=condition]').val(token);

				var formData = new FormData($frmDecAdd[0]);
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'behavior');
				formData.set('action', 'getConditionParams');

				genericAjaxPost(formData,null,null,function(html) {
					var $ul = $('#frmDecisionOutcome{$id} UL.rules:last');

					var seq = parseInt($frmDecAdd.find('input[name=seq]').val());
					if(null == seq)
						seq = 0;

					var $html = $('<div style="margin-left:20px;"/>').html(html);

					var $container = $('<li style="padding-bottom:5px;"/>').attr('id','condition' + seq + '_{$nonce}');
					$container.append($('<input type="hidden" name="nodes[]">').attr('value', seq));
					$container.append($('<input type="hidden">').attr('name', 'condition'+seq+'[condition]').attr('value',token));
					$container.append($('<a data-cerb-link="condition_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></a>'));
					$container.append('&nbsp;');
					$container.append($('<b style="cursor:move;"/>').text(label));
					$container.append('&nbsp;');
					$container.find('[data-cerb-link=condition_remove]').on('click', funcConditionRemove);
					$container.hide();

					$ul.append($container);
					$container.append($html).fadeIn();

					enhancePlaceholders($html);

					$html.find('.chooser_worker.unbound').each(function() {
						if(window.CerbUI && CerbUI.RecordChooser)
							new CerbUI.RecordChooser(this, { context:'cerberusweb.contexts.worker', name:'condition'+seq+'[worker_id]', multiple:true, emptyIcon:'user' });
						$(this).removeClass('unbound');
					});

					// [TODO] This can take too long to increment when packets are arriving quickly
					$frmDecAdd.find('input[name=seq]').val(1+seq);
				});

			}
		});

	}); // end popup_open

});
</script>
