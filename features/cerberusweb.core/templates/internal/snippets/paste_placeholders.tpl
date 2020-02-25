<form action="#" method="POST" id="formSnippetsPaste" name="formSnippetsPaste" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="snippet">
<input type="hidden" name="action" value="previewPlaceholders">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="context_id" value="{$context_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek" style="margin-bottom:5px;">
	<legend>Fill in the blanks:</legend>
	
	{foreach from=$snippet->custom_placeholders item=placeholder key=placeholder_key}
		<b>{$placeholder.label}</b>
		<div style="margin-left:10px;">
			{if $placeholder.type == Model_CustomField::TYPE_CHECKBOX}
				<label><input type="radio" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="1" {if $placeholder.default}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="0" {if !$placeholder.default}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
			{elseif $placeholder.type == Model_CustomField::TYPE_SINGLE_LINE}
				<input type="text" name="placeholders[{$placeholder_key}]" class="placeholder" required="required" value="{$placeholder.default}" style="width:98%;">
			{elseif $placeholder.type == Model_CustomField::TYPE_MULTI_LINE}
				<textarea name="placeholders[{$placeholder_key}]" class="placeholder" rows="3" cols="45" required="required" style="width:98%;">{$placeholder.default}</textarea>
			{/if}
		</div>
	{/foreach}
</fieldset>

<div class="buttons">
	<button type="button" class="paste"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Insert</button>
	<button type="button" class="preview">{'common.preview'|devblocks_translate|capitalize}</button>
</div>

<div class="preview"></div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('snippet_paste');
	
	$popup.one('popup_open',function(event,ui) {
		var $preview = $popup.find('div.preview');
		
		$popup.dialog('option','title', 'Insert Snippet');
		
		$popup.find('input:text,textarea').first().focus();

		$popup.find('div.buttons button.preview').click(function() {
			genericAjaxPost('formSnippetsPaste', '', null, function(html) {
				$preview.html(html);
			})
		});
		
		$popup.find('div.buttons button.paste').click(function() {
			var $elements = $popup
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
				var event = jQuery.Event('snippet_paste');
				event.text=text;
				
				$popup.trigger(event);
				
				genericAjaxPopupClose($popup);
			});
			
		});
		
	});
});
</script>
