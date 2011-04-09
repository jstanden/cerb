<form id="frmDecision" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveDecisionPopup">
{if isset($id)}<input type="hidden" name="id" value="{$id}">{/if}
{if isset($parent_id)}<input type="hidden" name="parent_id" value="{$parent_id}">{/if}
{if isset($type)}<input type="hidden" name="type" value="{$type}">{/if}
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title}" style="width:100%;"><br>
<br>

{$seq = 0}

{if empty($model->params.groups)}
	<fieldset style="cursor:pointer;">
		<legend>
			If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="all">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px;"></ul>
	</fieldset>

{else}
	{foreach from=$model->params.groups item=group_data}
	<fieldset style="cursor:pointer;">
		<legend>
			If <a href="javascript:;">{if !empty($group_data.any)}any{else}all{/if}&#x25be;</a> of these conditions are satisfied
			<a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a>
		</legend>
		<input type="hidden" name="nodes[]" value="{if !empty($group_data.any)}any{else}all{/if}">
		
		<ul class="rules" style="margin:0px;list-style:none;padding:0px;">
			{if isset($group_data.conditions) && is_array($group_data.conditions)}
			{foreach from=$group_data.conditions item=params}
				<li style="padding-bottom:5px;" id="condition{$seq}">
					<input type="hidden" name="nodes[]" value="{$seq}">
					<input type="hidden" name="condition{$seq}[condition]" value="{$params.condition}">
					<a href="javascript:;" onclick="$(this).closest('li').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a>
					<b>{$conditions.{$params.condition}.label}</b>&nbsp;
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

</form>

<form id="frmDecisionAdd">
<input type="hidden" name="seq" value="{$seq}">
<input type="hidden" name="condition" value="">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<fieldset>
	<legend>Add Condition</legend>

	<span class="cerb-sprite2 sprite-plus-circle-frame"></span>
	<button type="button" class="condition cerb-popupmenu-trigger">Add Condition &#x25be;</button>
	<button type="button" class="group">Add Group</button>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="16" class="input_search filter">
		</li>
		{foreach from=$conditions key=token item=condition}
		<li><a href="javascript:;" token="{$token}">{$condition.label}</a></li>
		{/foreach}
	</ul>
</fieldset>
</form>

<form>
	<button type="button" onclick="genericAjaxPost('frmDecision','','',function() { window.location.reload(); });"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Outcome");

		var $frm = $popup.find('form#frmDecision');
		var $legend = $popup.find('fieldset legend');
		var $menu = $popup.find('fieldset ul.cerb-popupmenu:first'); 

		$frm
			.find('fieldset ul.rules')
			.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'connectWith':'fieldset ul.rules' })
			;

		var $funcGroupAnyToggle = function(e) {
			$any = $(this).closest('fieldset').find('input:hidden:first');
			
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
			seq = $(this).closest('fieldset').find('input:hidden[name="conditions[]"]').val();
			ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});

		$frmAdd = $popup.find('#frmDecisionAdd');

		$frmAdd.find('button.group')
			.click(function(e) {
				$group = $('<fieldset style="cursor:pointer;"></fieldset>');
				$group.append('<legend>If <a href="javascript:;">all&#x25be;</a> of these conditions are satisfied <a href="javascript:;" onclick="$(this).closest(\'fieldset\').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a></legend>');
				$group.append('<input type="hidden" name="nodes[]" value="all">');
				$group.append('<ul class="rules" style="margin:0px;list-style:none;padding:0px;padding-bottom:5px;"></ul>');
				$group.find('legend > a').click($funcGroupAnyToggle);
				$group.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'connectWith':'fieldset ul.rules' });
				$frm.append($group);

				$frm.find('fieldset UL.rules')
					.sortable({ 'items':'li', 'placeholder':'ui-state-highlight', 'connectWith':'fieldset ul.rules' });
					;
			})
			;

		// Quick insert condition menu

		$menu_trigger = $frmAdd.find('button.condition.cerb-popupmenu-trigger');
		$menu = $frmAdd.find('ul.cerb-popupmenu');
		$menu_trigger.data('menu', $menu);

		$menu_trigger
			.click(
				function(e) {
					$menu = $(this).data('menu');

					if($menu.is(':visible')) {
						$menu.hide();
						return;
					}
					
					$menu
						.show()
						.find('> li input:text')
						.focus()
						.select()
						;
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

		$menu.find('> li').click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li'))
				return;

			$(this).find('a').trigger('click');
		});

		$menu.find('> li > a').click(function() {
			token = $(this).attr('token');
			$frmDecAdd = $('#frmDecisionAdd');
			$frmDecAdd.find('input[name=condition]').val(token);
			$this = $(this);
			
			genericAjaxPost('frmDecisionAdd','','c=internal&a=doDecisionAddCondition',function(html) {
				$ul = $('#frmDecision UL.rules:last');
				
				seq = parseInt($frmDecAdd.find('input[name=seq]').val());
				if(null == seq)
					seq = 0;

				$html = $('<div style="margin-left:20px;">' + html + '</div>');
				
				$container = $('<li style="padding-bottom:5px;" id="condition'+seq+'"></li>');
				$container.append('<input type="hidden" name="nodes[]" value="' + seq + '">');
				$container.append('<input type="hidden" name="condition'+seq+'[condition]" value="' + token + '">');
				$container.append('<a href="javascript:;" onclick="$(this).closest(\'li\').remove();"><span class="cerb-sprite2 sprite-minus-circle-frame"></span></a> ');
				$container.append('<b>' + $this.text() + '</b>&nbsp;');
				$container.append($html);
				$ul.append($container);

				$html.find('BUTTON.chooser_worker.unbound').each(function() {
					ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
					$(this).removeClass('unbound');
				});
				
				$menu.find('input:text:first').focus().select();

				$frmDecAdd.find('input[name=seq]').val(1+seq);
			});
		});			

	}); // end popup_open
</script>
