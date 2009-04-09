<b>{$translate->_('portal.cfg.url')}</b> {$translate->_('portal.cfg.url_hint')}<br>
<input type="text" name="base_url" size="65" value="{$base_url}"><br>
<br>

<b>{$translate->_('portal.cfg.logo_url')}</b> {$translate->_('portal.cfg.logo_url_hint')}<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>{$translate->_('portal.cfg.page_title')}</b> {$translate->_('portal.cfg.default_if_blank')}<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>{$translate->_('portal.cfg.style_css')}</b><br>
<textarea name="style_css" style="width:90%;height:150px;">{$style_css}</textarea><br>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('portal.sc.cfg.home_page')}</h2>
</div>

<div style="margin-left:10px;">

{$translate->_('portal.sc.cfg.feeds_info')}<br>
<br>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
	<td>
		<b>{$translate->_('portal.sc.cfg.feed_display_title')}</b>
	</td>
	<td>
		<b>{$translate->_('portal.sc.cfg.feed_url')}</b>
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
{$translate->_('portal.sc.cfg.save_more_feeds')}<br>
</div>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('portal.sc.cfg.footer')}</h2>
</div>

<div style="margin-left:10px;">
	<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			<b>{$translate->_('portal.sc.cfg.html')}</b> {$translate->_('portal.sc.cfg.footer_html_hint')}<br>
			<textarea cols="65" rows="8" name="footer_html">{$footer_html|escape}</textarea><br>
		</td>
		<td valign="top" width="100%" style="padding:10px;">
			<i>{$translate->_('portal.sc.cfg.example')}</i><br>
			{$translate->_('portal.sc.cfg.footer_html_example')|escape|nl2br}<br>
		</td>
	</tr>
	</table>
</div>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('portal.sc.cfg.login_registration')}</h2>
</div>

<label><input type="checkbox" name="allow_logins" value="1" {if $allow_logins}checked="checked"{/if}> {$translate->_('portal.sc.cfg.allow_customer_logins')}</label><br>
<br>

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('common.knowledgebase')|capitalize}</h2>
</div>

<div style="margin-left:10px;">
{$translate->_('portal.sc.cfg.choose_kb_topics')}<br>
<br>

{assign var=root_id value="0"}
{foreach from=$tree_map.$root_id item=category key=category_id}
	<label><input type="checkbox" name="category_ids[]" value="{$category_id}" {if isset($kb_roots.$category_id)}checked="checked"{/if}> <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top"> {$categories.$category_id->name}</label><br>
{/foreach}
</div>
<br>

{*
<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('common.search')|capitalize} ({$translate->_('common.fnr')|capitalize})</h2>
</div>

<div style="margin-left:10px;">
{$translate->_('portal.sc.cfg.fnr.info')}
<br>
<br>

{if !empty($topics)}
	{$translate->_('portal.sc.cfg.fnr.choose_resources')}<br>
	<br>
	{foreach from=$topics item=topic}
	<b>{$topic->name}</b><br>
	{foreach from=$topic->getResources() item=resource key=rid}
	<label><input type="checkbox" name="fnr_sources[]" value="{$resource->id}" {if isset($fnr_sources.$rid)}checked="checked"{/if}> {$resource->name}</label><br>
	{/foreach}
	<br>
	{/foreach}
{else}
	<div class="error">{$translate->_('portal.sc.cfg.fnr.not_configured')}</div>
{/if}
</div>
<br>
*}

<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$translate->_('portal.common.open_ticket')}</h2>
</div>

<label><input type="checkbox" name="allow_subjects" value="1" {if $allow_subjects}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.allow_custom_subjects')}</label><br>
<br>

<b>{$translate->_('portal.cfg.captcha')}</b> {$translate->_('portal.cfg.captcha_hint')}<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.enabled')}</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.disabled')}</label>
<br>
<br>

{foreach from=$dispatch item=params key=reason}
<div class="subtle" style="margin-bottom:10px;">
	<h2 style="display:inline;">{$reason}</h2>&nbsp;
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason={$reason|md5}&portal={$instance->code}');">{$translate->_('common.edit')|lower} </a>
	<br>
	<b>{$translate->_('portal.cfg.send_to')}</b> {$params.to}<br>
	{if is_array($params.followups)}
	{foreach from=$params.followups key=question item=field_id}
	<b>{$translate->_('portal.cfg.ask')}</b> {$question|escape} 
	{if $field_id}
		{assign var=field value=$ticket_fields.$field_id}
		{assign var=field_group_id value=$field->group_id}
		({if isset($groups.$field_group_id)}{$groups.$field_group_id->name}: {/if}{$field->name|escape})
	{/if}
	<br>
	{/foreach}
	<label><input type="checkbox" name="delete_situations[]" value="{$reason|md5}"> {$translate->_('portal.cfg.delete_situation')}</label>
	{/if}
</div>
{/foreach}

<div style="margin-left:10px;margin-bottom:10px;">
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason=&portal={$instance->code}');">{$translate->_('portal.cfg.add_new_situation')} </a>
</div>

<div class="subtle2" id="add_situation">
{include file="$config_path/portal/sc/config/add_situation.tpl"}
</div>
