<form id="frmDecisionAction{$id}Action" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
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

<div class="actions">

{$seq = null}
{if isset($model->params.actions) && is_array($model->params.actions)}
{foreach from=$model->params.actions item=params key=seq}
<fieldset id="action{$seq}">
	<legend style="cursor:move;">
		<a href="javascript:;" onclick="$(this).closest('fieldset').find('#divDecisionActionToolbar{$id}').hide().appendTo($('#frmDecisionAction{$id}Action'));$(this).closest('fieldset').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a>
		{if $actions.{$params.action}}
			{$actions.{$params.action}.label}
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

	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
	<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button>
	
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

<form id="frmDecisionActionAdd{$id}" action="javascript:;" onsubmit="return false;">
<input type="hidden" name="seq" value="{if !is_null($seq)}{$seq+1}{else}0{/if}">
<input type="hidden" name="action" value="">
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
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_action{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

{$status_div = "status_{uniqid()}"}

<form class="toolbar">
	{if !isset($id)}
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_action{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_action{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { Devblocks.showSuccess('#{$status_div}', 'Saved!'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'50%');"> <span class="glyphicons glyphicons-cogwheel"></span> Simulator</button>
		<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
</form>

<div id="{$status_div}" style="display:none;"></div>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('node_action{$id}');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if empty($id)}New {/if}Actions");
		$popup.find('input:text').first().focus();
		$popup.css('overflow', 'inherit');

		// Choosers
		
		$popup.find('BUTTON.chooser_group.unbound').each(function() {
			seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.group','action'+seq+'[group_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
		
		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
		$popup.find('BUTTON.chooser_notify_workers.unbound').each(function() {
			seq = $(this).closest('fieldset').find('input:hidden[name="actions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[notify_worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		$popup.find('#frmDecisionAction{$id}Action DIV.actions')
			.sortable({ 'items':'FIELDSET', 'placeholder':'ui-state-highlight', 'handle':'legend' })
		;

		// Placeholders
		
		$popup.find(':text.placeholders, textarea.placeholders')
			.atwho({
				{literal}at: '{%',{/literal}
				limit: 20,
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '${name}',{/literal}
				data: atwho_twig_commands,
				suffix: ''
			})
			.atwho({
				{literal}at: '|',{/literal}
				limit: 20,
				startWithSpace: false,
				searchKey: "content",
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '|${name}',{/literal}
				data: atwho_twig_modifiers,
				suffix: ''
			})
			;
		
		$popup.delegate(':text.placeholders, textarea.placeholders', 'focus', function(e) {
			var $toolbar = $('#divDecisionActionToolbar{$id}');
			var $src = $((null==e.srcElement) ? e.target : e.srcElement);
			
			if(0 == $src.nextAll('#divDecisionActionToolbar{$id}').length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				
				$toolbar.data('src', $src);
				
				// If a markItUp editor, move to parent
				if($src.is('.markItUpEditor')) {
					$src = $src.closest('.markItUp').parent();
					$toolbar.find('button.tester').hide();
					
				} else {
					$toolbar.find('button.tester').show();
				}
				
				$toolbar.show().insertAfter($src);
			}
		});
		
		// Placeholder menu
		
		var $divPlaceholderMenu = $('#divDecisionActionToolbar{$id}');
		
		var $placeholder_menu_trigger = $divPlaceholderMenu.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $divPlaceholderMenu.find('ul.menu').hide();
		
		// Quick insert token menu
		
		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				var $toolbar = $('DIV#divDecisionActionToolbar{$id}');
				var $field = null;
				
				if($toolbar.data('src')) {
					$field = $toolbar.data('src');
				
				} else {
					$field = $toolbar.prev(':text, textarea');
				}
				
				if(null == $field)
					return;
				
				$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
			}
		});
		
		$divPlaceholderMenu.find('button.tester').click(function(e) {
			var divTester = $divPlaceholderMenu.find('div.tester').first();
			
			var $toolbar = $('DIV#divDecisionActionToolbar{$id}');
			
			var $field = null;
			
			if($toolbar.data('src')) {
				$field = $toolbar.data('src');
			
			} else {
				$field = $toolbar.prev(':text, textarea');
			}
			
			if(null == $field)
				return;
			
			var regexpName = /^(.*?)(\[.*?\])$/;
			var hits = regexpName.exec($field.attr('name'));
			
			if(null == hits || hits.length < 3)
				return;
			
			var strNamespace = hits[1];
			var strName = hits[2];
			
			genericAjaxPost($(this).closest('form').attr('id'), divTester, 'c=internal&a=testDecisionEventSnippets&prefix=' + strNamespace + '&field=' + strName);
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
		
		var $frm = $('#frmDecisionActionAdd{$id}');
		var $actions_menu_trigger = $frm.find('button.action.cerb-popupmenu-trigger');
		var $actions_menu = $frm.find('ul.actions-menu');

		$actions_menu_trigger.click(function() {
			$actions_menu.toggle();
		});
		
		$actions_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$frm.find('input[name=action]').val(token);
				
				genericAjaxPost('frmDecisionActionAdd{$id}','','c=internal&a=doDecisionAddAction',function(html) {
					var $ul = $('#frmDecisionAction{$id}Action DIV.actions');
					
					var seq = parseInt($frm.find('input[name=seq]').val());
					if(null == seq)
						seq = 0;
		
					var $container = $('<fieldset/>').attr('id','action' + seq);
					$container.prepend('<legend style="cursor:move;"><a href="javascript:;" onclick="$(this).closest(\'fieldset\').find(\'#divDecisionActionToolbar{$id}\').hide().appendTo($(\'#frmDecisionAction{$id}Action\'));$(this).closest(\'fieldset\').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></a> ' + label + '</legend>');
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
					
					$html.find(':text.placeholders, textarea.placeholders')
						.atwho({
							{literal}at: '{%',{/literal}
							limit: 20,
							{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
							{literal}insertTpl: '${name}',{/literal}
							data: atwho_twig_commands,
							suffix: ''
						})
						.atwho({
							{literal}at: '|',{/literal}
							limit: 20,
							startWithSpace: false,
							searchKey: "content",
							{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
							{literal}insertTpl: '|${name}',{/literal}
							data: atwho_twig_modifiers,
							suffix: ''
						})
						;
					
					$frm.find('input[name=seq]').val(1+seq);
				});
				
			}
		});
		
	}); // popup_open
	
});
</script>