{*assign var=comment_address value=$comment->getAddress()*}
{if is_null($workers)}{$workers=DAO_Worker::getAll()}{/if}
{$worker = $workers.{$note.n_worker_id}}
<div id="note{$note.n_id}" class="block" style="margin-bottom:10px;">
	<h3 style="display:inline;"><span style="background-color:rgb(232,242,254);color:rgb(71,133,210);">{$translate->_('common.comment')|lower}</span> {if is_object($worker)}<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$worker->email|escape}', this, false, '500');" title="{$worker->email|escape}">{$worker->getName()}</a>{else}{'common.anonymous'|devblocks_translate}{/if}</h3> &nbsp;  
	{if $active_worker->is_superuser || $note.n_worker_id==$active_worker->id}
		<a href="javascript:;" onclick="if(confirm('Are you sure you want to permanently delete this comment?')) { genericAjaxGet('', 'c=internal&a=deleteNote&id={$note.n_id}', function(o) { $('#note{$note.n_id}').remove(); } ); } ">{$translate->_('common.delete')|lower}</a>
	{/if}	
	
	<br>
	{if isset($note.n_created)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$note.n_created|devblocks_date}<br>{/if}
	<pre>{$note.n_content|trim|escape|devblocks_hyperlinks}</pre>
</div>
