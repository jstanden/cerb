<b>URL to Logo:</b> (link to image, default if blank)<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>Page Title:</b> (default if blank)<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>Theme:</b> (default if blank)<br>
<select name="theme">
	{foreach from=$themes item=th}
	<option value="{$th}" {if $theme==$th}selected{/if}>{$th}</option>
	{/foreach}
</select><br>
<br>

<b>CAPTCHA:</b> (displays a CAPTCHA image in the form to help block automated spam)<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked{/if}> Enabled</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked{/if}> Disabled</label>
<br>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">Home Page</h2>
</div>

<div style="margin-left:10px;">
You can display content from RSS feeds on your Support Center home page, such as: the latest blog entries, 
popular knowledgebase articles, recent forum posts/announcements, etc.  This is the place to pull together 
all your interesting and helpful content, related to this portal, so the community can find it.<br>
<br>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
	<td>
		<b>Feed Display Title:</b>
	</td>
	<td>
		<b>Feed URL:</b>
	</td>
</tr>
{foreach from=$home_rss item=home_rss_url key=home_rss_title}
<tr>
	<td>
		<input type="text" name="home_rss_title[]" value="{$home_rss_title}" size="45">
	</td>
	<td>
		<input type="text" name="home_rss_url[]" value="{$home_rss_url}" size="45">
	</td>
</tr>
{/foreach}
{section name=home_rss start=0 loop=3}
<tr>
	<td>
		<input type="text" name="home_rss_title[]" value="" size="45">
	</td>
	<td>
		<input type="text" name="home_rss_url[]" value="" size="45">
	</td>
</tr>
{/section}
</table>
(save to add more feeds)<br>
</div>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">Footer</h2>
</div>

<div style="margin-left:10px;">
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			<b>HTML:</b> (e.g., organization, address, phone, fax, analytics)<br>
			<textarea cols="65" rows="8" name="footer_html">{$footer_html|escape}</textarea><br>
		</td>
		<td valign="top" width="100%" style="padding:10px;">
		<i>Example:</i><br>
		&lt;b&gt;Webgroup Media, LLC.&lt;/b&gt;&lt;br&gt;<br>
		451 W. Lambert Rd., Suite #201&lt;br&gt;<br>
		Brea, CA 92821 USA&lt;br&gt;<br>
		+1 714 681 9090&lt;br&gt;<br>
		</td>
	</tr>
	</table>
</div>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">Login/Registration</h2>
</div>

<label><input type="checkbox" name="allow_logins" value="1" {if $allow_logins}checked{/if}> Allow customer logins and registration (for viewing support history)</label><br>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">Search (Fetch &amp; Retrieve)</h2>
</div>

<div style="margin-left:10px;">
This Support Center will allow your community to search your external knowledge resources through 
Cerberus Helpdesk's <b>Fetch & Retrieve</b> system, returning direct links to content like knowledgebase 
articles, wiki articles, forum posts, documentation pages, blog entries, wishlist/bug reports, etc.<br>
<br>

{if !empty($topics)}
	Choose which resources to expose to this portal:<br>
	<br>
	{foreach from=$topics item=topic}
	<b>{$topic->name}</b><br>
	{foreach from=$topic->getResources() item=resource key=rid}
	<label><input type="checkbox" name="fnr_sources[]" value="{$resource->id}" {if isset($fnr_sources.$rid)}checked{/if}> {$resource->name}</label><br>
	{/foreach}
	<br>
	{/foreach}
{else}
	<div class="error">Fetch & Retrieve has not been configured.</div>
{/if}
</div>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">Open a Ticket</h2>
</div>

<label><input type="checkbox" name="allow_subjects" value="1" {if $allow_subjects}checked{/if}> Allow visitors to enter custom ticket subjects.</label><br>
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
{include file="$config_path/config/add_situation.tpl.php"}
</div>