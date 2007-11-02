{include file="$path/groups/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTeamBuckets">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Buckets</h2>

	<div style="margin-left:20px">
	{if !empty($categories)}
	<table cellspacing="2" cellpadding="0">
		<tr>
			<td><b>Bucket Name</b></td>
			<!-- <td><b>Access</b></td> -->
			<td><b>Remove</b></td>
		</tr>
		{foreach from=$categories item=cat key=cat_id name=cats}
			<tr>
				<td>
					<input type="hidden" name="ids[]" value="{$cat->id}">
					<input type="text" name="names[]" value="{$cat->name}" size="35">
				</td>
				<!-- 
				<td>
					<select name="access[]">
						<option value="">Private</option>
						<option value="">Shared</option>
					</select>
				</td>
				 -->
				<td align="center">
					<input type="checkbox" name="deletes[]" value="{$cat_id}">
				</td>
			</tr>
		{/foreach}
	</table>
	{else}
		<br>
		You haven't set up any buckets yet.  Buckets are containers which allow you to quickly organize the '{$team->name}' team workload.<br>
		<br>
		Example buckets:<br>
		<ul style="margin-top:0px;">
			<li>Receipts</li>
			<li>Newsletters</li>
			<li>Orders</li>
		</ul>
	{/if}
	<br>
	
	<h3>Add Buckets</h3>
	<b>Enter bucket names:</b> (one label per line)<br>
	<textarea rows="5" cols="45" name="add"></textarea><br>
	</div>
	
	<br>
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</div>

</form>