<form action="#" method="POST" id="formSnippetsPaste" name="formSnippetsPaste" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="snippetPlaceholdersPreview">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="context_id" value="{$context_id}">

<fieldset class="peek" style="margin-bottom:5px;">
	<legend>Fill in the blanks:</legend>
	
	{foreach from=$snippet->custom_placeholders item=placeholder key=placeholder_key}
		<b>{$placeholder.label}</b>
		<div style="margin-left:10px;">
			{if $placeholder.type == Model_CustomField::TYPE_CHECKBOX}
				<label><input type="radio" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="1" {if $placeholder.default}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="0" {if !$placeholder.default}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
			{elseif $placeholder.type == Model_CustomField::TYPE_SINGLE_LINE}
				<input type="text" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="{$placeholder.default}">
			{elseif $placeholder.type == Model_CustomField::TYPE_MULTI_LINE}
				<textarea name="placeholders[{$placeholder_key}]" class="placeholder" rows="3" cols="45" required="required" style="width:98%%;">{$placeholder.default}</textarea>
			{/if}
		</div>
	{/foreach}
</fieldset>

<div class="buttons">
	<button type="button" class="paste"><span class="cerb-sprite2 sprite-tick-circle"></span> Insert</button>
	<button type="button" class="preview">{'common.preview'|devblocks_translate|capitalize}</button>
</div>

<div class="preview"></div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('snippet_paste');
	$popup.one('popup_open',function(event,ui) {
		var $popup = $(this);
		var $preview = $popup.find('div.preview');
		
		$popup.dialog('option','title', 'Insert Snippet');
		
		$popup.find('input:text,textarea').first().focus();

		$popup.find('textarea').elastic();
		
		$popup.find('input:text').keyup(function() {
			var $val = $(this).val();
			
			if($val.length == 0 && null != $(this).attr('placeholder'))
				$val = $(this).attr('placeholder');
			
			var $span = $('<span style="visibility:hidden;">'+$val+'</span>').appendTo('body');
			var px = Math.max(25, $span.width() + 10);
			$(this).width(px);
			$span.remove();
		}).trigger('keyup');
		
		$popup.find('div.buttons button.preview').click(function() {
			genericAjaxPost('formSnippetsPaste', '', null, function(html) {
				$preview.html(html);
			})
		});
		
		$popup.find('div.buttons button.paste').click(function() {
			$elements = $popup
				.find('input:text.placeholder,textarea.placeholder')
				.filter(function() {
					if($(this).val().length == 0)
						return true;
					
					return false;
				})
				;
			
			// Require all the form elements
			if($elements.length > 0) {
				$elements.first().focus();
				return false;
			}
			
			// Build the text template
			genericAjaxPost('formSnippetsPaste', '', null, function(html) {
				$preview.hide().html(html);
				
				var text = $preview.text();
				
				// Fire event
				event=jQuery.Event('snippet_paste');
				event.text=text;
				
				$popup.trigger(event);
				
				genericAjaxPopupClose('snippet_paste');
			});
			
		});
		
	});
</script>
