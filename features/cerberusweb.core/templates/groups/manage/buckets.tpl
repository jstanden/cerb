<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabBuckets">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Buckets</h2>

	<div style="margin-left:20px">
		<table cellspacing="2" cellpadding="0">
			<tr>
				<td><b>{$translate->_('common.order')|capitalize}</b></td>
				<td style="padding-left:5px;"><b>{$translate->_('common.bucket')|capitalize}</b></td>
				<td style="padding-left:5px;"><b>Assignable</b></td>
				<td style="padding-left:5px;"><b>{$translate->_('common.remove')|capitalize}</b></td>
			</tr>
			
			{* Inbox *}
			<tr>
				<td align="center">
					--
				</td>
				<td style="padding-left:5px;">
					{$translate->_('common.inbox')|capitalize}
				</td>
				<td align="center" style="padding-left:5px;">
					<span class="ui-icon ui-icon-check"></span>
				</td>
				<td align="center" style="padding-left:5px;">
					<span class="ui-icon ui-icon-cancel"></span>
				</td>
			</tr>
			
			{* Buckets *}
			{if !empty($categories)}
			{foreach from=$categories item=cat key=cat_id name=cats}
				<tr>
					<td align="center">
						<input type="hidden" name="ids[]" value="{$cat->id}">
						<input type="text" name="pos[]" value="{counter name=bucket_pos}" size="2" maxlength="2">
					</td>
					<td style="padding-left:5px;">
						<input type="text" name="names[]" value="{$cat->name}" size="35">
					</td>
					<td align="center" style="padding-left:5px;">
						<label><input type="checkbox" name="is_assignable[]" value="{$cat_id}" {if $cat->is_assignable}checked="checked"{/if}></label>
					</td>
					<td align="center" style="padding-left:5px;">
						<input type="checkbox" name="deletes[]" value="{$cat_id}">
					</td>
				</tr>
			{/foreach}
			{/if}
		</table>
		<br>

		{if empty($categories)}
		<div class="subtle2">
			You haven't set up any buckets yet.  Buckets are containers which allow you to quickly organize the '{$team->name}' group workload.<br>
			<br>
			Example buckets:<br>
			<ul style="margin-top:0px;">
				<li>Receipts</li>
				<li>Newsletters</li>
				<li>Orders</li>
			</ul>
		</div>
		{/if}
	
		<h3>Add Buckets</h3>
		<b>Enter bucket names:</b> (one label per line)<br>
		<textarea rows="5" cols="45" name="add"></textarea><br>
	</div>
	
	<br>
	<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
</div>

</form>