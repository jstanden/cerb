<b>URL to Logo:</b> (link to image, default if blank)<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

{foreach from=$dispatch item=params key=reason}
<div class="subtle" style="margin-bottom:10px;">
	<h2 style="display:inline;">{$reason}</h2>&nbsp;
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=community&a=action&code={$instance->code}&action=getSituation&reason={$reason|md5}');">edit</a>
	<br>
	<b>Send to:</b> {$params.to}<br>
	{if is_array($params.followups)}
	{foreach from=$params.followups key=question item=long}
	<b>Ask:</b> {$question} {if $long}(Long Answer){/if}<br>
	{/foreach}
	{/if}
</div>
{/foreach}

<div class="subtle2" id="add_situation">
{include file="$config_path/portal/support/config/add_situation.tpl.php"}
</div>