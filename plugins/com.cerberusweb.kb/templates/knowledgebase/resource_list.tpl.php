{foreach from=$resources item=resource name=resources}
	<img src="images/document.gif" align="absmiddle"> <a href="#">{$resource.kb_title}</a><br>
{foreachelse}
	No resources are associated with this category.
{/foreach}
<br>