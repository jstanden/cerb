<form action="#" method="POST" id="formSnippetsPaste" name="formSnippetsPaste" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="snippet">
<input type="hidden" name="action" value="previewPrompts">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="context_id" value="{$context_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{include file="devblocks:cerberusweb.core::internal/snippets/prompts.tpl" prompts=$snippet->getPrompts()}

<div class="buttons" style="margin-top:5px;">
	<button type="button" class="paste"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Insert</button>
	<button type="button" class="preview">{'common.preview'|devblocks_translate|capitalize}</button>
</div>

<div class="preview"></div>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('snippet_paste');
	
	$popup.one('popup_open',function() {
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
					let $this = $(this);
					let $prompt = $this.closest('[data-cerb-snippet-prompt]');
					return $prompt.is('[data-required]') && 0 === $this.val().length;
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
				var event = $.Event('snippet_paste');
				event.text=text;
				
				$popup.trigger(event);
				
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>
