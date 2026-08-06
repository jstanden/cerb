<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3" id="widget{$widget->id}Config">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Custom HTML</div>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Display content using this template</label>
		<textarea name="params[content]" class="placeholders" data-editor-lines="10" spellcheck="false">{$widget->params.content}</textarea>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}Config');

	// Custom HTML template (Twig) — ScriptingEditor.
	if(window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor($widget.find('textarea[name="params[content]"]')[0]);
});
</script>