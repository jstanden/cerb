<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek black">
		<legend>{'common.comments'|devblocks_translate|capitalize}</legend>
		
		<label>
			<input type="radio" name="params[comments_mode]" value="0" {if !$widget->extension_params.comments_mode}checked="checked"{/if}>
			Show
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="2" {if 2 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			Show with the latest comment pinned at the top
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="1" {if 1 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			{'common.hide'|devblocks_translate|capitalize}
		</label>
	</fieldset>

	<fieldset class="peek" data-cerb-editor-toolbar>
		<legend>{'common.toolbar'|devblocks_translate|capitalize}: (KATA)</legend>

		<div class="cerb-code-editor-toolbar">
			{$toolbar_dict = DevblocksDictionaryDelegate::instance([
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id
			])}

			{$toolbar_kata =
"menu/insert:
  icon: circle-plus
  items:
    interaction/interaction:
      label: Interaction
      uri: ai.cerb.toolbarBuilder.interaction
    interaction/menu:
      label: Menu
      uri: ai.cerb.toolbarBuilder.menu
"}

			{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($toolbar_kata, $toolbar_dict)}

			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}

			<div class="cerb-code-editor-toolbar-divider"></div>

			<button type="button" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-circle-question-mark"></span></button>
		</div>

		<textarea name="params[message_toolbar_kata]" data-editor-mode="ace/mode/cerb_kata">{$widget->extension_params.message_toolbar_kata}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');

	var $editor_toolbar = $config.find('textarea[data-editor-mode]')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
	;

	var editor_toolbar = ace.edit($editor_toolbar.attr('id'));

	var $toolbar = $config.find('[data-cerb-editor-toolbar]');

	$toolbar.find('.cerb-code-editor-toolbar').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.editor',
			params: {
				toolbar: 'cerb.toolbar.profiles.ticket.message',
				selected_text: ''
			}
		},
		start: function(formData) {
			formData.set('caller[params][selected_text]', editor_toolbar.getSelectedText())
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			if(!e.eventData || !e.eventData.exit)
				return;

			if (e.eventData.exit === 'error') {
				// [TODO] Show error

			} else if(e.eventData.exit === 'return' && e.eventData.return.snippet) {
				editor_toolbar.insertSnippet(e.eventData.return.snippet);
			}
		}
	});
});
</script>