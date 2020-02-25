<div id="peekTemplateTest"></div>

{if !empty($placeholders)}<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button>{/if} 
<button type="button" data-cerb-button="toolbar-test">Test</button>
<button type="button" data-cerb-button="toolbar-help">Help</button>

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
	var $frm = $menu_trigger.closest('form');
	
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
			
			var $field = $content.next('pre.ace_editor');
			
			if(0 === $field.length) {
				if(token.match(/^\(\(__(.*?)__\)\)$/)) {
					$content.insertAtCursor(token);
				} else {
					{literal}$content.insertAtCursor('{{'+token+'}}');{/literal}
				}
				
			} else if($field.is('.ace_editor')) {
				var evt = new jQuery.Event('cerb.insertAtCursor');
				
				if(token.match(/^\(\(__(.*?)__\)\)$/)) {
					evt.content = token;
				} else {
					evt.content = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
				}
				
				$field.trigger(evt);
			}
		}
	});

	$popup.find('[data-cerb-button=toolbar-test]').on('click', function(e) {
		e.stopPropagation();

		var formData = new FormData($frm[0]);
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'snippet');
		formData.set('action', 'test');
		formData.set('snippet_field', 'content');
		formData.set('snippet_context', '{$context}');
		{if $context_id}formData.set('snippet_context_id', '{$context_id}');{/if}

		genericAjaxPost(formData,'peekTemplateTest',null);
	});

	$popup.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '600');
	});
});
</script>
{/if}