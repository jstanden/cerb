{if !empty($templates)}
	Click a title to insert the template into your e-mail at the cursor's position:<br>
{else}
	No e-mail templates have been created.
{/if}

{assign var=last_folder value=""}
{foreach from=$templates item=template key=template_id name=templates}
{if $template->folder != $last_folder}
	<h2>{$template->folder}</h2>
{/if}
<input type="radio" name="template_id" value="{$template_id}" {if $smarty.foreach.templates.first}checked{/if}> <span class="cerb-sprite sprite-document"></span> 
<a href="javascript:;" onclick="ajax.insertReplyTemplate('{$template_id}','{$txt_name}','{$reply_id}');"><b>{$template->title}</b></a> 
{if !empty($template->description)} - {$template->description}{/if}
<br>
{assign var=last_folder value=$template->folder}
{/foreach}
