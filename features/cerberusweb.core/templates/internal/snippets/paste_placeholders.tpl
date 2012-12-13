<form action="#" method="POST" id="formSnippetsPaste" name="formSnippetsPaste" onsubmit="return false;">

<fieldset class="peek">
<legend>Fill in the blanks:</legend>

<div class="emailbody">{$text = $text|escape}
{$text = $text|regex_replace:'#(\(\(____(.*?)____\)\))#':'<textarea rows="3" cols="45" class="placeholder placeholder-input" required="required" style="width:98%;" placeholder="\2"></textarea>'}
{$text = $text|regex_replace:'#(\(\(___(.*?)___\)\))#':'<input type="text" class="placeholder placeholder-input" required="required" placeholder="\2" value="\2">'}
{$text = $text|regex_replace:'#(\(\(__(.*?)__\)\))#':'<input type="text" class="placeholder placeholder-input" required="required" placeholder="\2">'}
{$text nofilter}</div>
</fieldset>

<div class="buttons">
	<button type="button" class="paste"><span class="cerb-sprite2 sprite-tick-circle"></span> Insert</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('snippet_paste');
	$popup.one('popup_open',function(event,ui) {
		var $popup = $(this);
		
		$popup.dialog('option','title', 'Snippet Placeholders');
		
		var $template = $(this).find('div.emailbody');
		
		$template.find('input:text,textarea').first().focus();
		
		$template.find('textarea').elastic();
		
		$template.find('input:text').keyup(function() {
			var $val = $(this).val();
			
			if($val.length == 0 && null != $(this).attr('placeholder'))
				$val = $(this).attr('placeholder');
			
			var $span = $('<span style="visibility:hidden;">'+$val+'</span>').appendTo('body');
			var px = Math.max(25, $span.width() + 10);
			$(this).width(px);
			$span.remove();
		}).trigger('keyup');

		$popup.find('div.buttons button.paste').click(function() {
			var $form = $(this).closest('form');
		
			$elements = $form.find('div.emailbody')
				.find('input:text.placeholder-input,textarea.placeholder-input')
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
			var text = $form.find('div.emailbody').contents().map(function() {
				var $this = $(this);
				
				if(this.nodeType == Node.TEXT_NODE) {
					return $this.text();
				} else {
					return $this.val();
				}
			}).get().join('');
			
			// Fire event
			event=jQuery.Event('snippet_paste');
			event.text=text;
			
			$popup.trigger(event);
			
			genericAjaxPopupClose('snippet_paste');
		});
	} );
</script>
