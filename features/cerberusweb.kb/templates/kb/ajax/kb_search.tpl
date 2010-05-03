<div style="background-color:rgb(240,240,240);margin:5px;padding:5px;">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="background-color:rgb(18,147,195);width:10px;"></td>
		<td style="padding-left:5px;">
			<H2>{$translate->_('common.knowledgebase')|capitalize}</H2>
		
			<form id="{$div}_kbsearch" action="#" method="POST" onsubmit="return false;">
			<input type="hidden" name="c" value="kb.ajax">
			<input type="hidden" name="a" value="doKbSearch">
			<input type="hidden" name="div" value="{$div}">
				
			<select name="topic_id">
				<option value="">- {$translate->_('display.reply.kb.all_topics')} -</option>
				{foreach from=$topics item=topic key=topic_id}
					<option value="{$topic_id}">{$topic->name|escape}</option>
				{/foreach}
			</select>
			<input type="text" name="q" size="24" value="{$q|escape}">
			<button type="button" name="btn_search" onclick="genericAjaxPost('{$div}_kbsearch', '{$div}_kbresults');"><span class="cerb-sprite sprite-data_find"></span> {$translate->_('common.search')|capitalize}</button>

			<label><input type="radio" name="scope" value="all" checked="checked"> all words</label>
			<label><input type="radio" name="scope" value="any"> any words</label>
			<label><input type="radio" name="scope" value="phrase"> phrase</label>
			<label><input type="radio" name="scope" value="expert"> expert</label>
			<br>
			<br>
			
			<div id="{$div}_kbresults"></div>
			
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPanel('c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite sprite-add"></span> Add Article</button>{/if}			
			<button type="button" onclick="$('#{$div}').html('');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.close')|capitalize}</button>
			</form>
		
		</td>
	</tr>
</table>
</div>
	
