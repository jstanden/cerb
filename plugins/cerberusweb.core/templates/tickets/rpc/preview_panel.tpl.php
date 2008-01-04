{assign var=headers value=$message->getHeaders()}
<h2>{$headers.subject|escape:"htmlall"}</h2>
<b>To:</b> {$headers.to|escape:"htmlall"}<br>
<b>From:</b> {$headers.from|escape:"htmlall"}<br>
<div style="width:98%;height:250px;overflow:auto;border:1px solid rgb(180,180,180);margin:2px;padding:3px;background-color:rgb(255,255,255);" ondblclick="if(null != genericPanel) genericPanel.hide();">
{$content|escape:"htmlall"|nl2br}
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="savePreview">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<b>Next Action:</b> <input type="text" name="next_action" size="45" maxlength="255" value="{$ticket->next_action|escape:"htmlall"}"><br>
<b>Next Worker:</b> 
<select name="next_worker_id">
	<option value="0" {if 0==$ticket->next_worker_id}selected{/if}>Anybody
	{foreach from=$workers item=worker key=worker_id}
		<option value="{$worker_id}" {if $worker_id==$ticket->next_worker_id}selected{/if}>{$worker->getName()}
	{/foreach}
</select><br>

<b>Bucket:</b>
<select name="bucket_id">
<option value="">-- move to --</option>
{if empty($ticket->category_id)}{assign var=t_or_c value="t"}{else}{assign var=t_or_c value="c"}{/if}
<optgroup label="Inboxes">
{foreach from=$teams item=team}
	<option value="t{$team->id}">{$team->name}{if $t_or_c=='t' && $ticket->team_id==$team->id} (current bucket){/if}</option>
{/foreach}
</optgroup>
{foreach from=$team_categories item=categories key=teamId}
	{assign var=team value=$teams.$teamId}
	{if !empty($active_worker_memberships.$teamId)}
		<optgroup label="-- {$team->name} --">
		{foreach from=$categories item=category}
		<option value="c{$category->id}">{$category->name}{if $t_or_c=='c' && $ticket->category_id==$category->id} (current bucket){/if}</option>
		{/foreach}
		</optgroup>
	{/if}
{/foreach}
</select><br>

<button type="button" onclick="ajax.postAndReloadView('frmTicketPeek','view{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
</form>