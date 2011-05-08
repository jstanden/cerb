<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSnippetsPeek" name="formSnippetsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveSnippetsPeek">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="context" value="{$snippet->context}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.title'|devblocks_translate|capitalize}</legend>
	
	<input type="text" name="title" value="{$snippet->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
</fieldset>

<fieldset>
	<legend>{'common.content'|devblocks_translate|capitalize}</legend>
	
	<textarea name="content" style="width:98%;height:250px;border:1px solid rgb(180,180,180);padding:2px;">{$snippet->content}</textarea>
	<br>
	
	{if !empty($token_labels)}
	<button type="button" class="cerb-popupmenu-trigger">Insert at cursor &#x25be;</button>
	<button type="button" onclick="genericAjaxPost('formSnippetsPeek','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$snippet->context}{if !empty($context_id)}&snippet_context_id={$context_id}{/if}&snippet_field=content');">Test</button>
	<div id="peekTemplateTest"></div>
	<ul class="cerb-popupmenu" style="border:0;">
		<li style="background:none;">
			<input type="text" size="16" class="input_search filter">
		</li>
		{foreach from=$token_labels key=token item=label}
		<li><a href="javascript:;" token="{$token}">{$label}</a></li>
		{/foreach}
	</ul>
	{/if}
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{if $active_worker->hasPriv('core.snippets.actions.create')}
	<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}')"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{else}
	<fieldset class="delete" style="font-weight:bold;">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($snippet->id)}
	{if $snippet->created_by==$active_worker->id || $active_worker->hasPriv('core.snippets.actions.update_all')}
	<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this snippet?')) { this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}'); } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>
	{/if}
{/if}
<br>
</form>

{if '' == $snippet->context}
{$context_name = 'Plaintext'}
{elseif isset($contexts.{$snippet->context})}
{$context_name = $contexts.{$snippet->context}->name}
{else}
{$context_name = $snippet->context}
{/if}
<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		{if empty($snippet->id)}
		$(this).dialog('option','title', 'Create Snippet ({$context_name})');
		{else}
		$(this).dialog('option','title', 'Modify Snippet ({$context_name})');
		{/if}

		var $menu = $popup.find('ul.cerb-popupmenu'); 

		// Quick insert token menu

		$menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
		$menu_trigger.data('menu', $menu);

		$menu_trigger
			.click(
				function(e) {
					$menu = $(this).data('menu');

					if($menu.is(':visible')) {
						$menu.hide();
						return;
					}
					
					$menu
						.show()
						.find('> li input:text')
						.focus()
						.select()
						;
				}
			)
		;

		$menu.find('> li > input.filter').keyup(
			function(e) {
				term = $(this).val().toLowerCase();
				$menu = $(this).closest('ul.cerb-popupmenu');
				$menu.find('> li a').each(function(e) {
					if(-1 != $(this).html().toLowerCase().indexOf(term)) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}
		);

		$menu.find('> li').click(function(e) {
			e.stopPropagation();
			if(!$(e.target).is('li'))
				return;

			$(this).find('a').trigger('click');
		});

		$menu.find('> li > a').click(function() {
			token = $(this).attr('token');
			$content = $popup.find('textarea[name=content]');
			{literal}$content.insertAtCursor('{{'+token+'}}');{/literal}
			$content.focus();
		});				
	} );
</script>
