<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Render this template:</div>
		</div>

		<textarea id="widget{$widget->id}TemplateEditor" name="params[template]" data-editor-lines="8" spellcheck="false">{$widget->extension_params.template}</textarea>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	new CerbUI.ScriptingEditor(document.getElementById('widget{$widget->id}TemplateEditor'), { minLines: 4 });
});
</script>