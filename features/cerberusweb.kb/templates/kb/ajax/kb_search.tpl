<div class="block" style="width:98%;">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
			<h2>{$translate->_('common.knowledgebase')|capitalize}</h2>
		
			<form id="{$div}_kbsearch" action="#" method="POST" onsubmit="genericAjaxPost('{$div}_kbsearch', 'view{$view_id}');return false;">
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
			<button type="submit" name="btn_search"><span class="cerb-sprite sprite-data_find"></span> {$translate->_('common.search')|capitalize}</button>

			<label><input type="radio" name="scope" value="all" checked="checked"> all words</label>
			<label><input type="radio" name="scope" value="any"> any words</label>
			<label><input type="radio" name="scope" value="phrase"> phrase</label>
			<label><input type="radio" name="scope" value="expert"> expert</label>
			<br>
			<br>
			
			<div id="view{$view_id}"></div>
			
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite sprite-add"></span> Add Article</button>{/if}			
			<button type="button" onclick="$('#{$div}').html('');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.close')|capitalize}</button>
			</form>
		
		</td>
	</tr>
</table>
</div>
	
<script type="text/javascript">
$('#{$div}_kbsearch input:text:first').focus();
</script>
