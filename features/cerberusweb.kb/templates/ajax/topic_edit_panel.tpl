<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveTopicEditPanel">
<input type="hidden" name="id" value="{$topic->id}">
<input type="hidden" name="delete_box" value="0">

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

{if $active_worker->hasPriv('core.kb.topics.modify')}<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>{/if}
{if $active_worker->hasPriv('core.kb.topics.modify') && !empty($topic)}<button type="button" onclick="toggleDiv('deleteTopic','block');"><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.remove')|capitalize}</button>{/if}
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Knowledgebase Topic");
	} );
</script>
