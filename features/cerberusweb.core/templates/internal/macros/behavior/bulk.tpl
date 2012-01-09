{if !empty($macros)}
<fieldset>
	<legend>Schedule Behavior</legend>

	<table width="100%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top" align="right">
				{'common.behavior'|devblocks_translate|capitalize}:
			</td>
			<td width="99%" nowrap="nowrap" valign="top">
				<select name="behavior_id" onchange="$div=$(this).next('div');$val=$(this).val();if($val.length==0){ $div.html(''); return; };genericAjaxGet($div,'c=internal&a=showScheduleBehaviorBulkParams&trigger_id=' + $val);">
				<option value=""></option>
				{foreach from=$macros item=v key=k}
					<option value="{$k}">{$v->title}</option>
				{/foreach}
				</select>
				<div>
				</div>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top" align="right">
				When:
			</td>
			<td width="99%" nowrap="nowrap" valign="top">
				<input type="text" name="behavior_when" value="now" size="16" style="width:98%;" onfocus="$(this).next('div').fadeIn();" onblur="$(this).next('div').fadeOut();">
				<div style="display:none;">
					 e.g. now; +2 hours; Monday 8am; Dec 21 2012; tomorrow
				</div>
			</td>
		</tr>
	</table>
</fieldset>
{/if}