{include file="$tpl_path/portal/kb/header.tpl.php"}

<table style="text-align: left; width: 100%;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="padding: 5px; vertical-align: top;">
				<form action="{devblocks_url}{/devblocks_url}" method="post" name="articleForm" onsubmit="myEditor.saveHTML();">
				<input type="hidden" name="a" value="doArticleEdit">
				<input type="hidden" name="id" value="{if !empty($article)}{$article->id}{else}0{/if}">
				<h2>Article Editor</h2>
				
				<b>Title:</b><br>
				<input type="text" name="title" size="64" maxlength="128" value="{$article->title}"><br>
				<br>
				
				<textarea name="content" id="article_content" rows="10" cols="80">{$article->content}</textarea><br>
				
				<b>Tags:</b> (comma-separated)<br>
				<input type="text" name="tags" size="64" value="{if !empty($tags)}{foreach from=$tags item=tag name=tags}{$tag->name}{if !$smarty.foreach.tags.last}, {/if}{/foreach}{/if}" maxlength="255"><br>
				<br>
				
				<button type="submit">{$translate->_('common.save_changes')}</button>
				</form>
			</td>
		</tr>
	</tbody>
</table>

<script>
{literal}
var myEditor = null;
YAHOO.util.Event.addListener(window,"load",function() {
	myEditor = new YAHOO.widget.Editor('article_content', {
	    height: '300px',
	    width: '650px',
	    dompath: false,
	    animate: false
		});	    
	myEditor.render();
	});
{/literal}
</script>

{include file="$tpl_path/portal/kb/footer.tpl.php"}