<div style="background-color:rgb(240,240,240);margin:5px;padding:5px;">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="background-color:rgb(18,147,195);width:10px;"></td>
		<td style="padding-left:5px;">
			<H2>{$translate->_('common.knowledgebase')|capitalize}</H2>
		
			<form action="#" method="GET" onsubmit="this.btn_search.click();this.q.select();return false;">
			<select name="topic_id">
				<option value="">- {$translate->_('display.reply.kb.all_topics')} -</option>
				{foreach from=$topics item=topic key=topic_id}
					<option value="{$topic_id}">{$topic->name|escape}</option>
				{/foreach}
			</select>
			<input type="text" name="q" value="{$q|escape}">
			<button type="button" name="btn_search" onclick="genericAjaxGet('{$div}_kbresults','c=kb.ajax&a=doKbSearch&div={$div}&q='+encodeURIComponent(this.form.q.value)+'&topic_id='+selectValue(this.form.topic_id));"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/data_find.gif{/devblocks_url}" align="top"> {$translate->_('common.search')|capitalize}</button>
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'550');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_new.gif{/devblocks_url}" align="top"> Add Article</button>{/if}
			<br>
			<br>
			
			<div id="{$div}_kbresults"></div>
			
			<button type="button" onclick="clearDiv('{$div}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.close')|capitalize}</button>
			</form>
		
		</td>
	</tr>
</table>
</div>
	
