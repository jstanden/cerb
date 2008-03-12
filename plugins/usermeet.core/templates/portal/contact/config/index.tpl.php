<b>URL to Logo:</b> (link to image, default if blank)<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>Page Title:</b> (default if blank)<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>CAPTCHA:</b> (displays a CAPTCHA image in the form to help block automated spam)<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked{/if}> Enabled</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked{/if}> Disabled</label>
<br>
<br>

{foreach from=$dispatch item=params key=reason}
<div class="subtle" style="margin-bottom:10px;">
	<h2 style="display:inline;">{$reason}</h2>&nbsp;
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason={$reason|md5}&portal={$instance->code}');">edit </a>
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
{include file="$config_path/portal/contact/config/add_situation.tpl.php"}
</div>