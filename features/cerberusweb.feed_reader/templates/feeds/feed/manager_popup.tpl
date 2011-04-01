<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmFeedsManagerPopup">
<input type="hidden" name="c" value="feeds">
<input type="hidden" name="a" value="saveFeedsManagerPopup">

<fieldset>
	<legend>{'feeds.common'|devblocks_translate}</legend>

	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<th align="left" width="35%">{'common.name'|devblocks_translate|capitalize}</th>
			<th align="left" width="55%">{'common.url'|devblocks_translate|upper}</th>
			<th align="center" width="10%"></th>
		</tr>
		{foreach from=$feeds item=feed}
		<tbody>
			<tr>
				<td>
					<input type="hidden" name="feed_id[]" value="{$feed->id}">
					<input type="text" name="feed_name[]" value="{$feed->name}" style="width:95%;">
				</td>
				<td>
					<input type="text" name="feed_url[]" value="{$feed->url}" class="input_rss" style="width:95%;">
				</td>
				<td align="center" nowrap="nowrap">
					<button type="button" class="add" style="display:none;" onclick="$table=$(this).closest('table');$tbody=$(this).closest('tbody').clone();$tbody.appendTo($table).find('input:text').val('').focus();$table.find('button.del').show().last().hide();$(this).hide();">+</button>
					<button type="button" class="del" onclick="$table=$(this).closest('table');$(this).closest('tbody').remove();$table.find('button.add:last').show();">-</button>
				</td>
			</tr>
		</tbody>
		{/foreach}
		<tbody>
			<tr>
				<td>
					<input type="hidden" name="feed_id[]" value="">
					<input type="text" name="feed_name[]" value="" style="width:95%;">
				</td>
				<td>
					<input type="text" name="feed_url[]" value="" class="input_rss" style="width:95%;">
				</td>
				<td align="center" nowrap="nowrap">
					<button type="button" class="add" onclick="$table=$(this).closest('table');$tbody=$(this).closest('tbody').clone();$tbody.appendTo($table).find('input:text').val('').focus();$table.find('button.del').show().last().hide();$(this).hide();">+</button>
					<button type="button" class="del" onclick="$table=$(this).closest('table');$(this).closest('tbody').remove();$table.find('button.add:last').show();" style="display:none;">-</button>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmFeedsManagerPopup','{$view_id}',false,'feedsmanager_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('feeds.manage')}");
	});
</script>
