<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddOrgNote">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgNote">
<input type="hidden" name="org_id" value="{$org->id}">
<input type="hidden" name="id" value="">

<b>Add a note about {$org->name}</b><br>
<textarea name="content" rows="3" cols="65" style="width:500px;height:50px;"></textarea><br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

<br>

{foreach from=$notes item=note}
	{assign var=worker_id value=$note.n_worker_id}
	<div style="border-top:1px dashed rgb(200,200,200);padding:5px;margin-top:5px;margin-bottom:5px;" onmouseover="toggleDiv('contactDelOrgNote{$note.n_id}','inline');" onmouseout="toggleDiv('contactDelOrgNote{$note.n_id}','none');">
		<b style="color:rgb(0,120,0);">{$workers.$worker_id->getName()}</b> 
		{$note.n_content} <span style="font-size:90%;color:rgb(175,175,175);">{$note.n_created|devblocks_date}</span>
		{if $active_worker->is_superuser || $active_worker->id == $worker_id}
			<span style="display:none;padding:2px;" id="contactDelOrgNote{$note.n_id}"><a href="javascript:;" onclick="document.getElementById('btnDelOrgNote{$note.n_id}').click();" style="font-size:90%;color:rgb(230,0,0);">delete</a></span>
			<button type="button" id="btnDelOrgNote{$note.n_id}" style="display:none;visibility:hidden;" onclick="if(confirm('Are you sure you want to delete this note?')){literal}{{/literal}this.form.a.value='deleteOrgNote';this.form.id.value='{$note.n_id}';this.form.submit();{literal}}{/literal}"></button>
		{/if}
	</div>
{/foreach}

</form>
