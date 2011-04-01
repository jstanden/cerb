<div class="block" style="width:98%;">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
			<h2>{$translate->_('common.knowledgebase')|capitalize}</h2>
		
			<form id="{$div}_kbsearch" action="#" method="POST" onsubmit="return false;">
			<input type="hidden" name="c" value="kb.ajax">
			<input type="hidden" name="a" value="doKbSearch">
			<input type="hidden" name="div" value="{$div}">
				
			<select name="topic_id">
				<option value="">- {$translate->_('display.reply.kb.all_topics')} -</option>
				{foreach from=$topics item=topic key=topic_id}
					<option value="{$topic_id}">{$topic->name}</option>
				{/foreach}
			</select>
			<b>{'common.search'|devblocks_translate|capitalize}:</b>
			<input type="text" name="q" size="24" value="{$q}">

			<label><input type="radio" name="scope" value="all" checked="checked"> all words</label>
			<label><input type="radio" name="scope" value="any"> any words</label>
			<label><input type="radio" name="scope" value="phrase"> phrase</label>
			<label><input type="radio" name="scope" value="expert"> expert</label>
			</form>
			<br>
			
			<div id="view{$view_id}"></div>
			
			<form action="#" onsubmit="return false">
			{if $active_worker->hasPriv('core.kb.articles.modify')}<button type="button" onclick="genericAjaxPopup('peek','c=kb.ajax&a=showArticleEditPanel&id=0&root_id={$root_id}&view_id={$view->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Add Article</button>{/if}			
			<button type="button" onclick="$('#{$div}').html('');"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.close')|capitalize}</button>
			</form>
		
		</td>
	</tr>
</table>
</div>
	
<script type="text/javascript">
	$('#{$div}_kbsearch input[name=q]')
	.focus()
	.keypress(function(event) {
		if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
			return;

		if(event.which == 13 || event.which == 10) {
			$this = $(this);
			genericAjaxPost('{$div}_kbsearch', null, null, function(html) {
				$('#view{$view_id}').html(html).fadeIn();
				$this.select().focus();
			});
		}
	})
	;
</script>
