{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-query" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<textarea name="prompts[{$var}]" data-editor-mode="ace/mode/cerb_query" data-editor-line-numbers="false" placeholder="{$placeholder}">{$value|default:$default}</textarea>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $prompt = $('#{$element_id}');
		var $form = $prompt.closest('form');

		var $editor = $prompt.find('textarea')
			.cerbCodeEditor()
		;

		{if $record_type}
		$editor.cerbCodeEditorAutocompleteSearchQueries({
			context: "{$record_type}"
		});
		{/if}

		$editor = $editor.nextAll('pre.ace_editor')

		var editor = ace.edit($editor.attr('id'));
		editor.setOption('highlightActiveLine', false);
		editor.renderer.setOption('showGutter', false);

		// Move cursor to the end of the text
		editor.navigateFileEnd();
		
		editor.commands.addCommand({
			name: 'Submit',
			bindKey: { win: "Enter", mac: "Enter" },
			exec: function() {
				$form.triggerHandler($.Event('cerb-form-builder-submit'));
			}
		});
	});
</script>