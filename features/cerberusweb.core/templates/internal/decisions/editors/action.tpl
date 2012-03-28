<form id="frmDecisionAction{$id}Action" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

<div class="actions">

{$seq = null}
{if isset($model->params.actions) && is_array($model->params.actions)}
{foreach from=$model->params.actions item=params key=seq}
<fieldset id="action{$seq}">
	<legend style="cursor:move;">
		<a href="javascript:;" onclick="$(this).closest('fieldset').find('#divDecisionActionToolbar{$id}').hide().appendTo($('#frmDecisionAction{$id}Action'));$(this).closest('fieldset').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a>
		{$actions.{$params.action}.label}
	</legend>
	
	<input type="hidden" name="actions[]" value="{$seq}">
	<input type="hidden" name="action{$seq}[action]" value="{$params.action}">
	
	{$event->renderAction({$params.action},$trigger,$params,$seq)}
</fieldset>
{/foreach}
{/if}

</div>

<div id="divDecisionActionToolbar{$id}" style="display:none;">
	<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert &#x25be;</button>
	<ul class="cerb-popupmenu cerb-float" style="margin-top:-5px;">
		<li style="background:none;">
			<input type="text" size="18" class="input_search filter">
		</li>
		{foreach from=$labels key=k item=v}
		<li><a href="javascript:;" token="{$k}">{$v}</a></li>
		{/foreach}
	</ul>
	<button type="button" class="tester">{'common.test'|devblocks_translate|capitalize}</button>
	<div class="tester"></div>
</div>

</form>

<form id="frmDecisionActionAdd{$id}">
<input type="hidden" name="seq" value="{if !is_null($seq)}{$seq+1}{else}0{/if}">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<fieldset>
	<legend>Add Action</legend>

	<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
	<select name="action">
		<option value=""></option>
		{foreach from=$actions item=action key=token}
		<option value="{$token}">{$action.label}</option>
		{/foreach}
	</select>
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
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_action{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_action{$id}'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="cerb-sprite2 sprite-folder-tick-circle"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPost('frmDecisionAction{$id}Action','','c=internal&a=saveDecisionPopup',function() { Devblocks.showSuccess('#{$status_div}', 'Saved!'); genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger_id}','reuse',false,'500');"> <span class="cerb-sprite sprite-gear"></span> Simulator</button>
		<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
</form>

<div id="{$status_div}" style="display:none;"></div>	

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('node_action{$id}');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Actions");
		$(this).find('input:text').first().focus();

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

		$popup.delegate(':text.placeholders, textarea.placeholders', 'focus', function(e) {
			toolbar = $('#divDecisionActionToolbar{$id}');
			src = (null==e.srcElement) ? e.target : e.srcElement;
			if(0 == $(src).nextAll('#divDecisionActionToolbar{$id}').length) {
				toolbar.find('div.tester').html('');
				toolbar.show().insertAfter(src);
			}
		});
		
		$popup.find('#frmDecisionActionAdd{$id} SELECT').first().change(function() {
			$select = $(this);
			$val=$select.val();
	
			if(''==$val) {
				return;
			}
	
			genericAjaxPost('frmDecisionActionAdd{$id}','','c=internal&a=doDecisionAddAction',function(html) {
				$ul = $('#frmDecisionAction{$id}Action DIV.actions');
				
				seq = parseInt($('#frmDecisionActionAdd{$id}').find('input[name=seq]').val());
				if(null == seq)
					seq = 0;
	
				$container = $('<fieldset id="action' + seq + '"></fieldset>');
				$container.prepend('<legend style="cursor:move;"><a href="javascript:;" onclick="$(this).closest(\'fieldset\').find(\'#divDecisionActionToolbar{$id}\').hide().appendTo($(\'#frmDecisionAction{$id}Action\'));$(this).closest(\'fieldset\').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a> ' + $select.find('option:selected').text() + '</legend>');
				$container.append('<input type="hidden" name="actions[]" value="' + seq + '">');
				$container.append('<input type="hidden" name="action'+seq+'[action]" value="' + $select.val() + '">');
				$ul.append($container);
	
				$html = $('<div>' + html + '</div>');
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
				
				$select.val(0);
	
				$('#frmDecisionActionAdd{$id}').find('input[name=seq]').val(1+seq);
			});
		});

		// Menu
		
		$divPlaceholderMenu = $('#divDecisionActionToolbar{$id}');
		
		$menu_trigger = $divPlaceholderMenu.find('button.cerb-popupmenu-trigger');
		$menu = $divPlaceholderMenu.find('ul.cerb-popupmenu').appendTo('body');
		$menu_trigger.data('menu', $menu);
		
		$divPlaceholderMenu.find('button.tester').click(function(e) {
			var divTester = $(this).nextAll('div.tester').first();
			
			$toolbar = $('DIV#divDecisionActionToolbar{$id}');
			$field = $toolbar.prev(':text, textarea');			
			
			if(null == $field)
				return;
			
			regexpName = /^(.*?)\[(.*?)\]$/;
			hits = regexpName.exec($field.attr('name'));
			
			if(null == hits || hits.length < 3)
				return;
			
			strNamespace = hits[1];
			strName = hits[2];
			
			genericAjaxPost($(this).closest('form').attr('id'), divTester, 'c=internal&a=testDecisionEventSnippets&prefix=' + strNamespace + '&field=' + strName);			
		});
		
		$menu_trigger
			.click(
				function(e) {
					$menu = $(this).data('menu');
					$menu
						.css('position','absolute')
						.css('top',($(this).offset().top+20)+'px')
						.css('left',$(this).offset().left+'px')
						.show()
						.find('> li input:text')
						.focus()
						.select()
						;
				}
			)
			.bind('remove',
				function(e) {
					$menu = $(this).data('menu');
					$menu.remove();
				}
			)
		;
		
		$menu.find('> li > input.filter').keyup(
			function(e) {
				term = $(this).val().toLowerCase();
				$menu = $(this).closest('ul.cerb-popupmenu');
				$menu.find('> li a').each(function(e) {
					if(-1 != $(this).html().toLowerCase().indexOf(term)) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}
		);
		
		$menu.hover(
			function(e) {
			},
			function(e) {
				$(this).hide();
			}
		);
		
		$menu.find('> li').click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li'))
				return;
		
			$(this).find('a').trigger('click');
		});
		
		$menu.find('> li > a').click(function() {
			$toolbar = $('DIV#divDecisionActionToolbar{$id}');
			$field = $toolbar.prev(':text, textarea');
			
			if(null == $field)
				return;
			
			strtoken = $(this).attr('token');
			
			$field.focus().insertAtCursor('{literal}{{{/literal}' + strtoken + '{literal}}}{/literal}');
			$(this).closest('ul.cerb-popupmenu').hide();
		});		
		
	}); // popup_open
</script>
