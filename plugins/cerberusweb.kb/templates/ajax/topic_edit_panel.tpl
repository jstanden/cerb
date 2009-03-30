<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveTopicEditPanel">
<input type="hidden" name="id" value="{$topic->id}">
<input type="hidden" name="delete_box" value="0">

{if !empty($topic)}
<h1>Modify Topic</h1>
{else}
<h1>Add Topic</h1>
{/if}

<b>Name:</b><br>
<input type="text" name="name" value="{$topic->name|escape}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
<br>

<div id="deleteTopic" style="display:none;">
	<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:0px;padding:5px;">
		<h3>Delete Topic</h3>
		You're about to remove this topic and all its subcategories. Your 
		article content will not be deleted, but articles will be removed  
		from these categories.<br>
		<button type="button" onclick="this.form.delete_box.value='1';this.form.submit();">Delete</button>
		<button type="button" onclick="this.form.delete_box.value='0';toggleDiv('deleteTopic','none');">Cancel</button>
	</div>
	<br>
</div>

{if $active_worker->hasPriv('core.kb.topics.modify')}<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>{/if}
{if $active_worker->hasPriv('core.kb.topics.modify') && !empty($topic)}<button type="button" onclick="toggleDiv('deleteTopic','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
<button type="button" onclick="genericPanel.hide();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

</form>