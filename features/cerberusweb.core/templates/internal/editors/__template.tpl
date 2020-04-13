{$frm_id = "form{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$frm_id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<textarea name="content" style="width:98%;height:350px;border:1px solid rgb(180,180,180);padding:2px;" class="placeholders">{$template}</textarea>

<div class="toolbar" style="margin-bottom:10px;">
	<div id="peekTemplateTest"></div>
	
	<button type="button" class="cerb-popupmenu-trigger">Insert placeholder &#x25be;</button> 
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
</div>
	
<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$frm_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title', '{{'Template Editor'|capitalize|escape:'javascript'}}');
		$popup.css('overflow', 'inherit');

		var $textarea = $popup.find('textarea[name=content]');
		var $toolbar = $popup.find('.toolbar');
		
		// Buttons
		$popup.find('button.submit').on('click', function() {
			var evt = jQuery.Event('template_save');
			evt.template = $textarea.val();
			genericAjaxPopupClose($popup, evt);
		});

		$toolbar.find('[data-cerb-button=toolbar-test]').on('click', function(e) {
			e.stopPropagation();

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'snippet');
			formData.set('action', 'test');
			formData.set('snippet_context', '{$context_ext->id}');
			formData.set('snippet_key_prefix', '{$key_prefix}');
			formData.set('snippet_field', 'content');

			genericAjaxPost(formData,'peekTemplateTest',null);
		});

		$toolbar.find('[data-cerb-button=toolbar-help]').on('click', function(e) {
			e.stopPropagation();
			genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top' , at:'left+20 top+20'}, false, '50%');
		});

		// Editor
		var $menu_trigger = $popup.find('button.cerb-popupmenu-trigger');
		var $placeholder_menu = $popup.find('ul.menu').hide();
		
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
				
				var $field = $textarea.siblings('pre.ace_editor');
				
				if($field.is(':text, textarea')) {
					$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
					
				} else if($field.is('.ace_editor')) {
					var evt = new jQuery.Event('cerb.insertAtCursor');
					evt.content = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
					$field.trigger(evt);
				}
			}
		});
		
		// Snippet syntax
		$textarea
			.cerbCodeEditor()
			;
	});
});
</script>