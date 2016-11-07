<div id="peekTemplateTest"></div>

{if !empty($placeholders)}<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button>{/if} 
<button type="button" onclick="genericAjaxPost('{$form_id}','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$context}{if !empty($context_id)}&snippet_context_id={$context_id}{/if}&snippet_field=content');">Test</button> 
<button type="button" onclick="genericAjaxPopup('help', 'c=internal&a=showSnippetHelpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');">Help</button> 

{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul>
					{tree keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<ul class="menu" style="width:250px;">
{tree keys=$placeholders}
</ul>
	
{if !empty($placeholders)}
<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#peekTemplateTest');
	var $menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
	var $placeholder_menu = $popup.find('ul.menu').hide();
	var $content = $popup.find('textarea[name=content]');
	
	$menu_trigger.click(function() {
		$placeholder_menu.toggle();
	});
	
	// Quick insert token menu
	
	$placeholder_menu.menu({
		select: function(event, ui) {
			var token = ui.item.attr('data-token');
			var label = ui.item.attr('data-label');
			
			if(undefined == token || undefined == label)
				return;
			
			if(token.match(/^\(\(__(.*?)__\)\)$/)) {
				$content.insertAtCursor(token);
			} else {
				{literal}$content.insertAtCursor('{{'+token+'}}');{/literal}
			}
			$content.focus();
		}
	});
});
</script>
{/if}