{if !empty($templates)}
	Click a title to insert the template into your reply at the cursor:<br>
{else}
	No e-mail templates have been created.
{/if}

{assign var=last_folder value=""}
{foreach from=$templates item=template key=template_id name=templates}
{if $template->folder != $last_folder}
	<h2>{$template->folder}</h2>
{/if}
<input type="radio" name="template_id" value="{$template_id}" {if $smarty.foreach.templates.first}checked{/if}> <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document.gif{/devblocks_url}" align="absmiddle"> 
<a href="javascript:;" onclick="displayAjax.insertReplyTemplate('{$template_id}','{$reply_id}');"><b>{$template->title}</b></a> 
{if !empty($template->description)} - {$template->description}{/if}
<br>
{assign var=last_folder value=$template->folder}
{/foreach}
