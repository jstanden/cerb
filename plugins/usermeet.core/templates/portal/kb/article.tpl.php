{include file="$tpl_path/portal/kb/header.tpl.php"}

<table style="text-align: left; width: 100%;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="padding: 5px; vertical-align: top;">
				<div id="kbArticle">
				<h2 style="margin:0px;">{$article->title}</h2>
				{if !empty($tags)}
					<a href="{devblocks_url}c=browse&path={$location}{/devblocks_url}"><b>Location</b></a>: 
					{foreach from=$tags item=tag name=tags}
						<a href="{devblocks_url}c=browse&path={$tag->name|escape:"url"}{/devblocks_url}">{$tag->name}</a>{if !$smarty.foreach.tags.last} + {/if}
					{/foreach}
					<br>
				{/if}
				<br>
				{$article->content}<br>
				<br>
				<!-- 
				<div style="margin: 10px; padding: 10px; background-color: rgb(230, 230, 230);">
					<h3 style="margin-top: 2px;">Provide Feedback</h3>
					<b>Did this article help you?</b><br>
					(_) Yes (_) No<br>
					<br>
					<b>Comments:</b><br>
					<textarea style="width: 100%; height: 80px;"></textarea><br>
					<br>
					<button type="button">Submit</button>
					<br>
				</div>
				 -->
				 </div>
			</td>
			<td style="width: 200px; white-space: nowrap; vertical-align: top;">
				<div style="border-left: 1px solid rgb(200, 200, 200); border-right: 1px solid rgb(200, 200, 180); padding: 10px; margin-right: 5px; background-color: rgb(245, 245, 255);">
					<form action="{devblocks_url}c=search{/devblocks_url}" method="post">
					<input type="hidden" name="a" value="doSearch">
					<b>Search</b><br>
					<input name="query" value="" size="16" style="width: 150px;" type="text"><button type="submit">&raquo;</button>
					</form>
					<br>
					<b>Other Resources</b><br>
					<a href="#">Contact Us</a><br>
					<br>
					<b>Article Tools</b><br>
					<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/printer.gif{/devblocks_url}" alt="Printer" align="top"> <a href="#" onclick="window.print();">Print</a><br>
					{if !empty($editor) && !empty($article)}<img src="{devblocks_url}c=resource&p=usermeet.core&f=images/document_edit.gif{/devblocks_url}" alt="Edit" align="top"> <a href="{devblocks_url}c=edit&id={$article->id}{/devblocks_url}">Edit</a><br>{/if}
				</div>
			</td>
		</tr>
	</tbody>
</table>

{include file="$tpl_path/portal/kb/footer.tpl.php"}