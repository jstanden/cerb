<form id="frmDecisionOutcome{$id}" onsubmit="return false;" method="post">
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
	<input type="text" name="title" value="{$model->title}" style="width:100%;" autocomplete="off" spellcheck="false">
</div>

<b>{'common.status'|devblocks_translate|capitalize}:</b>
<div style="margin:0px 0px 10px 10px;">
	<label><input type="radio" name="status_id" value="0" {if !$model->status_id}checked="checked"{/if}> Live</label>
	<label><input type="radio" name="status_id" value="2" {if 2 == $model->status_id}checked="checked"{/if}> Simulator only</label>
	<label><input type="radio" name="status_id" value="1" {if 1 == $model->status_id}checked="checked"{/if}> Disabled</label>
</div>

{$seq = 0}

{if empty($model->params.groups)}
	<fieldset>
		<legend>
			If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="all">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;"></ul>
	</fieldset>

{else}
	{foreach from=$model->params.groups item=group_data}
	<fieldset>
		<legend>
			If <a href="javascript:;">{if !empty($group_data.any)}any{else}all{/if}&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').trigger('cerb.remove');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="{if !empty($group_data.any)}any{else}all{/if}">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px 0px 2px 0px;">
			{if isset($group_data.conditions) && is_array($group_data.conditions)}
			{foreach from=$group_data.conditions item=params}
				<li style="padding-bottom:5px;" id="condition{$seq}_{$nonce}">
					<input type="hidden" name="nodes[]" value="{$seq}">
					<input type="hidden" name="condition{$seq}[condition]" value="{$params.condition}">
					<a href="javascript:;" onclick="$(this).closest('li').trigger('cerb.remove');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
					<b style="cursor:move;">{$conditions.{$params.condition}.label}</b>&nbsp;
					<div style="margin-left:20px;">
						{$event->renderCondition({$params.condition},$trigger,$params,$seq)}
					</div>
				</li>
				{$seq = $seq + 1}
			{/foreach}
			{/if}
		</ul>
	</fieldset>
	{/foreach}
{/if}

<div id="divDecisionOutcomeToolbar{$id}" style="display:none;">
	<div class="tester"></div>
	
	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
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

<form id="frmDecisionOutcomeAdd{$id}" action="javascript:;" onsubmit="return false;" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="getConditionParams">
<input type="hidden" name="seq" value="{$seq}">
<input type="hidden" name="condition" value="">
<input type="hidden" name="nonce" value="{$nonce}">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{'common.conditions'|devblocks_translate|capitalize}</legend>

	<button type="button" class="condition cerb-popupmenu-trigger">{'common.condition'|devblocks_translate|capitalize} &#x25be;</button>
	<button type="button" class="group">Add Group</button>
	
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
	
</fieldset>
</form>

{if isset($id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this outcome?</legend>
	<p>Are you sure you want to permanently delete this outcome and its children?</p>
	<button type="button" class="red" data-cerb-button="delete-confirm"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" data-cerb-button="delete-reject"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="toolbar">
	{if !isset($id)}
		<button type="button" data-cerb-button="save-create"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" data-cerb-button="save-close"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
		<button type="button" data-cerb-button="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		<button type="button" data-cerb-button="simulator"> <span class="glyphicons glyphicons-cogwheel"></span> Simulator</button>
		<button type="button" data-cerb-button="delete"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_outcome{$id}');
	
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
		var $legend = $popup.find('fieldset legend');
		var $toolbar = $('DIV#divDecisionOutcomeToolbar{$id}');

		$popup.find('[data-cerb-button=save-create]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_outcome{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=save-close]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				genericAjaxPopupDestroy('node_outcome{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=save-continue]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPost($frm,null,null,function() {
				Devblocks.createAlert('Saved!', 'note');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=simulator]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'50%');
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
				genericAjaxPopupDestroy('node_outcome{$id}');
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			});
		});

		$popup.find('[data-cerb-button=delete-reject]').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('fieldset').hide().next('.toolbar').show();
		});

		$popup.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20' }, false, '600');
		});

		$frm.find('fieldset UL.rules')
			.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'handle':'> b', 'connectWith':'#frmDecisionOutcome{$id} fieldset ul.rules' })
			;

		var $funcGroupAnyToggle = function(e) {
			var $any = $(this).closest('fieldset').find('input:hidden:first');

			if("any" == $any.val()) {
				$(this).html("all&#x25be;");
				$any.val('all');
			} else {
				$(this).html("any&#x25be;");
				$any.val('any');
			}
		}

		$legend.find('a').click($funcGroupAnyToggle);

		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			var seq = $(this).closest('li').find('input:hidden[name="nodes[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		var $frmAdd = $popup.find('#frmDecisionOutcomeAdd{$id}');

		$frmAdd.find('button.group')
			.click(function(e) {
				var $group = $('<fieldset></fieldset>');
				$group.append('<legend>If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied <a href="javascript:;" onclick="$(this).closest(\'fieldset\').trigger(\'cerb.remove\');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a></legend>');
				$group.append('<input type="hidden" name="nodes[]" value="all">');
				$group.append('<ul class="rules" style="margin:0px;list-style:none;padding:0px;padding-bottom:5px;"></ul>');
				$group.find('legend > a').click($funcGroupAnyToggle);
				$frm.append($group);

				$frm.find('fieldset UL.rules')
					.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'handle':'> b', 'connectWith':'#frmDecisionOutcome{$id} fieldset ul.rules' })
					;
			})
			;

		// Placeholders

		$popup.find('textarea.placeholders, :text.placeholders').cerbCodeEditor();

		$popup.delegate(':text.placeholders, textarea.placeholders, pre.placeholders', 'focus', function(e) {
			e.stopPropagation();

			var $target = $(e.target);
			var $parent = $target.closest('.ace_editor');

			if(0 !== $parent.length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				$toolbar.show().insertAfter($parent);
				$toolbar.data('src', $parent);

			} else {
				if(0 === $target.nextAll('#divDecisionOutcomeToolbar{$id}').length) {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter($target);
					$toolbar.data('src', $target);
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

				if(undefined === token || undefined === label)
					return;

				var $field = null;

				if($toolbar.data('src')) {
					$field = $toolbar.data('src');
				} else {
					$field = $toolbar.prev(':text, textarea');
				}

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

			var $field;

			if($toolbar.data('src')) {
				$field = $toolbar.data('src');
			} else {
				$field = $toolbar.prev(':text, textarea');
			}

			if(null == $field)
				return;

			if($field.is('.ace_editor')) {
				$field = $field.prev('textarea, :text');
			}

			if($field.is(':text, textarea')) {
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
			}
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

		// Quick insert condition menu

		var $conditions_menu_trigger = $frmAdd.find('button.condition.cerb-popupmenu-trigger');
		var $conditions_menu = $frmAdd.find('ul.conditions-menu');

		$conditions_menu_trigger.click(function() {
			$conditions_menu.toggle();
		});

		$conditions_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');

				if(undefined === token || undefined === label)
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
					$container.append($('<a href="javascript:;" onclick="$(this).closest(\'li\').trigger(\'cerb.remove\');"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>'));
					$container.append('&nbsp;');
					$container.append($('<b style="cursor:move;"/>').text(label));
					$container.append('&nbsp;');
					$container.hide();

					$ul.append($container);
					$container.append($html).fadeIn();

					$html.find('textarea.placeholders, :text.placeholders').cerbCodeEditor();

					$html.find('BUTTON.chooser_worker.unbound').each(function() {
						ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
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