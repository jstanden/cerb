<input type="hidden" name="oper" value="in">

<b>{'search.oper.in_list'|devblocks_translate}:</b><br>
<ul style="list-style:none;padding:0px;margin:0px;">
{foreach from=$requesters item=requester key=requester_id}
	<li>
		<label><input type="checkbox" name="requester_ids[]" value="{$requester_id}"> {$requester->email}</label>
	</li>
{/foreach}
</ul>