{foreach from=$resources item=resource name=resources}
	<img src="{devblocks_url}images/document.gif{/devblocks_url}" align="absmiddle"> <a href="#">{$resource.kb_title}</a><br>
{foreachelse}
	No resources are associated with this category.<br>
{/foreach}
<br>