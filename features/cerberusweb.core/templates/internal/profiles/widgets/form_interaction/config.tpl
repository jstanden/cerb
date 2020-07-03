<div style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions: <small>(Kata)</small></legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" data-cerb-button="interactions-preview" class="cerb-code-editor-toolbar-button"><span class="glyphicons glyphicons-play"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>
			<button type="button" class="cerb-code-editor-toolbar-button cerb-button-toolbar-add" title="Add interaction"><span class="glyphicons glyphicons-circle-plus"></span></button>
			<ul class="cerb-float" style="display:none;">
				<li data-type="interaction"><b>{'common.interaction'|devblocks_translate|capitalize}</b></li>
				<li data-type="menu"><b>{'common.menu'|devblocks_translate|capitalize}</b></li>
			</ul>
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/bots/interactions/forms/" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		<textarea name="params[interactions_kata]" class="cerb-code-editor placeholders" data-editor-mode="ace/mode/yaml" style="width:100%;">{$widget->extension_params.interactions_kata}</textarea>
		<div class="cerb-code-editor-preview-output"></div>
	</fieldset>
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $config = $script.prev('div');
	var $form = $config.closest('form');

	var $editor = $config.find('.cerb-code-editor')
		.cerbCodeEditor()
		.next('pre.ace_editor')
	;

	var editor = ace.edit($editor.attr('id'));

	var $placeholder_output = $config.find('.cerb-code-editor-preview-output');

	$config.find('button[data-cerb-button="interactions-preview"]').on('click', function (e) {
		e.stopPropagation();
		$placeholder_output.html('');

		Devblocks.getSpinner().appendTo($placeholder_output);

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'profile_widget');
		formData.set('action', 'invokeConfig');
		formData.set('config_action', 'previewInteractions');
		formData.set('interactions_kata', editor.getValue());

		var $hidden = $form.find('input[name=id]');

		if(0 === $hidden.length) {
			var $select = $form.find('select[name=extension_id]');

			if(0 === $select.length)
				return;

			formData.set('id', $select.val());
			formData.set('profile_tab_id', $form.find('input[name=profile_tab_id]'));

		} else {
			formData.set('id', $hidden.val());
		}
		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	})
	;

	var $toolbar_button_add = $config.find('.cerb-button-toolbar-add');

	var $toolbar_button_add_menu = $toolbar_button_add.next('ul').menu({
		"select": function(e, $ui) {
			e.stopPropagation();
			$toolbar_button_add_menu.hide();

			var data_type = $ui.item.attr('data-type');

			if(null == data_type)
				return;

			if('interaction' === data_type) {
				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'chooserOpen');
				formData.set('context', 'behavior');
				formData.set('single', '1');
				formData.set('q', 'uri:!null');
				formData.set('qr', 'event:event.form.interaction.worker disabled:no');

				var $chooser = genericAjaxPopup(Devblocks.uniqueId(), formData, null, true, '90%');

				$chooser.on('chooser_save', function(e) {
					var behavior_id = e.values[0];

					var formData = new FormData();
					formData.set('c', 'ui');
					formData.set('a', 'dataQuery');

					var query = 'type:worklist.records of:behavior format:dictionaries query:(limit:1 id:' + behavior_id + ')';
					formData.set('q', query);

					genericAjaxPost(formData, null, null, function(json) {
						if ('object' == typeof json && json.data && json.data[behavior_id]) {
							{literal}
							var behavior_dict = json.data[behavior_id];
							var widget_name = $form.find('input[name=name]').val();
							var snippet = "interaction/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:" + behavior_dict._label + "}\n  name: " + behavior_dict.uri + "\n  inputs:\n";

							if(behavior_dict.variables && 0 !== behavior_dict.variables.length) {
								for (var var_key in behavior_dict.variables) {
									if (!behavior_dict.variables.hasOwnProperty(var_key))
										continue;

									var variable = behavior_dict.variables[var_key];

									if (!variable.is_private) {
										snippet += "    " + variable.key + ": \n";
									}
								}
							}

							snippet += "  #icon: circle-ok\n  #hidden@bool: \n    #{% if row_selections is empty %}yes{% endif %}\n  event/done:\n    refresh_widgets@list:\n      " + widget_name + "${4:}\n";
							$editor.triggerHandler($.Event('cerb.insertAtCursor', { content: snippet } ));
							{/literal}
						}
					});
				});

			} else if('menu' === data_type) {
				{literal}
				var snippet = "menu/${1:" + Devblocks.uniqueId() + "}:\n  label: ${2:Name}\n  #icon: menu-hamburger\n  #hidden@bool: no\n  items:\n    ${3:}\n";
				$editor.triggerHandler($.Event('cerb.insertAtCursor', { content: snippet } ));
				{/literal}
			}
		}
	});

	$toolbar_button_add.on('click', function() {
		$toolbar_button_add_menu.toggle();
	});
});
</script>