<b>{$translate->_('portal.cfg.url')}</b> {$translate->_('portal.cfg.url_hint')}<br>
<input type="text" name="base_url" size="65" value="{$base_url}"><br>
<br>

<b>{$translate->_('portal.cfg.logo_url')}</b> {$translate->_('portal.cfg.logo_url_hint')}<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>{$translate->_('portal.cfg.page_title')}</b> {$translate->_('portal.cfg.default_if_blank')}<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<!--
<b>CAPTCHA:</b> (displays a CAPTCHA image in the form to help block automated spam)<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked{/if}> Enabled</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked{/if}> Disabled</label>
<br>
<br>
 -->

{*
<h3>{$translate->_('portal.cfg.public_categories')}</h3>
{$translate->_('portal.cfg.public_categories_hint')}<br>

<div style="overflow:auto;height:150px;border:solid 1px rgb(180,180,180);background-color:rgb(255,255,255);">
	{foreach from=$levels item=depth key=node_id}
		<label>
			<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.color=(this.checked)?'green':'';div.style.background=(this.checked)?'rgb(230,230,230)':'';" {if isset($kb_roots.$node_id)}checked{/if}>
			<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/tree_cap.gif{/devblocks_url}" align="absmiddle">{else}<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="absmiddle">{/if} <span id="kbTreeCat{$node_id}" {if isset($kb_roots.$node_id)}style="color:green;background-color:rgb(230,230,230);"{/if}>{$categories.$node_id->name}</span></span>
		</label>
		<br>
	{/foreach}
</div>
*}

<h3>{$translate->_('portal.kb.cfg.public_topics')}</h3>
{assign var=root_id value="0"}
{foreach from=$tree_map.$root_id item=category key=category_id}
	<label><input type="checkbox" name="category_ids[]" value="{$category_id}" {if isset($kb_roots.$category_id)}checked{/if}> <img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/folder.gif{/devblocks_url}" align="top"> {$categories.$category_id->name}</label><br>
{/foreach}


