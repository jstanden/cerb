{$config_uniqid = uniqid('widgetConfig_')}
<div id="cardWidgetConfig{$config_uniqid}" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions: <small>(Kata)</small></legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" data-cerb-button="interactions-preview" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-play"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" data-cerb-button="interaction-add" class="cerb-code-editor-toolbar-button" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query-required="event:event.form.interaction.worker private:no uri:!&quot;&quot;"><span class="glyphicons glyphicons-circle-plus"></span></button>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/bots/interactions/forms/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		<textarea name="params[interactions_kata]" class="cerb-code-editor placeholders" data-editor-mode="ace/mode/yaml">{$widget->extension_params.interactions_kata}</textarea>
		<div class="cerb-code-editor-preview-output"></div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#cardWidgetConfig{$config_uniqid}');
	var $form = $config.closest('form');

	var $editor = $config.find('.cerb-code-editor')
		.cerbCodeEditor()
		.next('pre.ace_editor')
		;

	var editor = ace.edit($editor.attr('id'));

	$config.find('button.chooser-interaction')
		.cerbChooserTrigger()
	;

	var $placeholder_output = $config.find('.cerb-code-editor-preview-output');

	$config.find('button[data-cerb-button="interactions-preview"]').on('click', function (e) {
		e.stopPropagation();
		$placeholder_output.html('');

		$('<span class="cerb-ajax-spinner"/>').appendTo($placeholder_output);

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'card_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewInteractions');
		formData.set('interactions_kata', editor.getValue());

		var $hidden = $form.find('input[name=id]');

		if(0 === $hidden.length) {
			var $select = $form.find('select[name=extension_id]');

			if(0 === $select.length)
				return;

			formData.set('id', $select.val());
			formData.set('record_type', $form.find('input[name=record_type]'));

		} else {
			formData.set('id', $hidden.val());
		}
		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	});
	;

	$config.find('button[data-cerb-button="interaction-add"]')
		.click(function() {
			var $trigger = $(this);
			var context = $trigger.attr('data-context');
			var query = $trigger.attr('data-query');
			var query_req = $trigger.attr('data-query-required');

			var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context);

			if($trigger.attr('data-single'))
				chooser_url += '&single=1';

			if(typeof query == 'string' && query.length > 0) {
				chooser_url += '&q=' + encodeURIComponent(query);
			}

			if(typeof query_req == 'string' && query_req.length > 0) {
				chooser_url += '&qr=' + encodeURIComponent(query_req);
			}

			var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

			$chooser.one('chooser_save', function(e) {
				if(!e.values || !$.isArray(e.values) || 0 === e.values.length)
					return;

				var behavior_id = e.values[0];

				var formData = new FormData();
				formData.set('c', 'ui');
				formData.set('a', 'dataQuery');

				var query = 'type:worklist.records of:behavior format:dictionaries query:(limit:1 id:' + behavior_id + ')';
				formData.set('q', query);

				genericAjaxPost(formData, null, null, function(json) {
					if('object' == typeof json && json.data && json.data[behavior_id]) {
						var behavior_dict = json.data[behavior_id];

						{literal}
						var snippet = behavior_dict.uri + "/" + Devblocks.uniqueId() + ":\n"
							+"  button:\n"
							+"    label: ${1:" + behavior_dict.name + "}\n"
							+"    #icon: circle-ok\n"
							+"    hidden@bool: no\n"
						;
						{/literal}

						if(behavior_dict.variables && 0 !== behavior_dict.variables.length) {
							snippet += "  params:\n";
							for(var var_key in behavior_dict.variables) {
								if(!behavior_dict.variables.hasOwnProperty(var_key))
									continue;

								var variable = behavior_dict.variables[var_key];

								if(!variable.is_private) {
									snippet += "    " + variable.key;

									if('L' === variable.type) {
										snippet += "@key: record_id";

									} else {
										snippet += ": ";
									}

									snippet += "\n";
								}
							}
						}

						snippet += "\n";

						$editor.triggerHandler($.Event('cerb.appendText', { content: snippet } ));
					}
				});
			});
		})
	;
});
</script>