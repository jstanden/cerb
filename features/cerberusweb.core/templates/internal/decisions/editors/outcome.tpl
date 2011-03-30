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

<fieldset>
	<legend>
		If
		{*
		<select name="scope">
			<option value="all" selected="selected">all</option>
			<option value="any">any</option>
		</select>
		*}
		<u>all</u> of these conditions are satisfied
	</legend>

	<ul class="rules" style="margin:0px;list-style:none;padding:0px;">
		{$seq = null}
		{foreach from=$model->params item=params key=seq}
			<li style="padding-bottom:5px;">
				<input type="hidden" name="conditions[]" value="{$seq}">
				<input type="hidden" name="condition{$seq}[condition]" value="{$params.condition}">
				<b>{$conditions.{$params.condition}.label}</b>&nbsp;
				<a href="javascript:;" onclick="$(this).closest('li').remove();"><span class="cerb-sprite sprite-forbidden"></span></a>
				<div style="margin-left:20px;">
					{$event->renderCondition({$params.condition},$trigger,$params,$seq)}
				</div>
			</li>
		{/foreach}
	</ul>
</fieldset>
</form>

<form id="frmDecisionAddCondition">
<input type="hidden" name="seq" value="{if !is_null($seq)}{$seq+1}{else}0{/if}">
{if isset($trigger_id)}<input type="hidden" name="trigger_id" value="{$trigger_id}">{/if}
<fieldset>
	<legend>Add Condition</legend>

	<span class="cerb-sprite sprite-add"></span>
	<select name="condition">
		<option value=""></option>
		{foreach from=$conditions item=condition key=token}
		<option value="{$token}">{$condition.label}</option>
		{/foreach}
	</select>
</fieldset>
</form>

<form>
	<button type="button" onclick="genericAjaxPost('frmDecision','','',function() { window.location.reload(); });"><span class="cerb-sprite sprite-check"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if empty($id)}New {/if}Outcome");

		$popup.find('BUTTON.chooser_worker.unbound').each(function() {
			seq = $(this).closest('li').find('input:hidden').first().val();
			ajax.chooser(this,'cerberusweb.contexts.worker','condition'+seq+'[worker_id]', { autocomplete:true });
			$(this).removeClass('unbound');
		});
	});

	$popup.find('#frmDecisionAddCondition SELECT').first().change(function() {
		$select = $(this);
		$val=$select.val();

		if(''==$val) {
			//$select.siblings('#divAddCondition').html('');
			//$select.siblings('div').hide();
			return;
		}

		genericAjaxPost('frmDecisionAddCondition','','c=internal&a=doDecisionAddCondition',function(html) {
			$ul = $('#frmDecision UL.rules');
			
			seq = parseInt($('#frmDecisionAddCondition').find('input[name=seq]').val());
			if(null == seq)
				seq = 0;

			$html = $('<div style="margin-left:20px;">' + html + '</div>');
			$html.find('[name]').each(function() {
				name = $(this).attr('name');
				$(this).attr('name', 'condition' + seq + name); // condition0...condition99
			});

			$container = $('<li style="padding-bottom:5px;"></li>');
			$container.append('<input type="hidden" name="conditions[]" value="' + seq + '">');
			$container.append('<input type="hidden" name="condition'+seq+'[condition]" value="' + $select.val() + '">');
			$container.append('<b>' + $select.find('option:selected').text() + '</b>&nbsp;');
			$container.append('<a href="javascript:;" onclick="$(this).closest(\'li\').remove();"><span class="cerb-sprite sprite-forbidden"></span></a>');
			$container.append($html);
			$ul.append($container);

			$html.find('BUTTON.chooser_worker.unbound').each(function() {
				ajax.chooser(this,'cerberusweb.contexts.worker','action'+seq+'[worker_id]', { autocomplete:true });
				$(this).removeClass('unbound');
			});
			
			$select.val(0);

			$('#frmDecisionAddCondition').find('input[name=seq]').val(1+seq);
		});

	});
</script>
