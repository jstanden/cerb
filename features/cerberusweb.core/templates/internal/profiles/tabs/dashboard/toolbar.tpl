{$uniqid = uniqid('toolbar')}
<div id="{$uniqid}">
	<div class="tester"></div>

	<ul class="cerb-ui-toolbar">
		<li data-value="placeholders" data-icon="placeholders" title="Insert placeholder"></li>
		<li></li>
		<li data-value="test" data-icon="play" title="{'common.test'|devblocks_translate|capitalize}"></li>
		<li data-value="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
	</ul>

	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{if is_array($data->children) && !empty($data->children)}
				<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
					{if $data->key}
						<div style="font-weight:bold;">{$data->l|capitalize}</div>
					{else}
						<div>{$idx|capitalize}</div>
					{/if}
					<ul>
						{tree keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif $data->key}
				<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
			{/if}
		{/foreach}
	{/function}

	<ul class="menu cerb-float" style="width:250px;display:none;">
	{tree keys=$placeholders}
	</ul>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $toolbar = $div.parent(); // .cerb-placeholder-menu — carries data('src'), the focused field
	var $placeholder_menu = $div.find('ul.menu').hide();
	var toolbar_ul = $div.find('ul.cerb-ui-toolbar')[0];

	// The field the strip is currently attached to (set by the peek's focus delegate).
	var activeField = function() {
		var $field = $toolbar.data('src') || $toolbar.prev(':text, textarea');
		return ($field && $field.length) ? $field : null;
	};

	// Quick insert token menu — a separate filterable menu (the toolbar doesn't forward `filter` to submenus)
	var menu = new CerbUI.Menu($placeholder_menu[0], {
		selectableParents: true,
		filter: true,
		onSelect: function(li, src) {
			var token = src.getAttribute('data-token');
			var label = src.getAttribute('data-label');

			if(undefined == token || undefined == label)
				return;

			var $field = activeField();

			if($field)
				$field.focus().insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
		}
	});

	var runTest = function() {
		var divTester = $div.find('div.tester').first();
		var $field = activeField();

		if(!$field)
			return;

		var field_key = $field.attr('name');
		var $widget_params = $field.closest('.cerb-widget-params');

		// Disambiguate same-named fields by index
		$widget_params.find('[name="' + field_key + '"]')
			.each(function(index) {
				var $this = $(this);

				if($this.is($field)) {
					var formData = new FormData($div.closest('form')[0]);
					formData.set('c', 'profiles');
					formData.set('a', 'invoke');
					formData.set('module', 'profile_widget');
					formData.set('action', 'testWidgetTemplate');
					formData.set('template_key', field_key);
					formData.set('index', index);

					genericAjaxPost(formData, divTester, '');
				}
			}
		);
	};

	if(toolbar_ul && window.CerbUI && CerbUI.Toolbar) {
		new CerbUI.Toolbar(toolbar_ul, {
			onSelect: function(item, sourceLi, e) {
				switch(item.value) {
					case 'placeholders':
						menu.open((e && (e.currentTarget || e.target)) || sourceLi);
						var $field = activeField();
						if($field) $field.focus();
						break;

					case 'test':
						runTest();
						break;

					case 'help':
						CerbUI.Dialog.fromAjax('c=profiles&a=invoke&module=snippet&action=helpPopup', {
							title: "{'common.help'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
							width: '80%'
						});
						break;
				}
			}
		});
	}

	// Teardown the menu with the toolbar
	$div.on('remove', function() { if(menu) menu.destroy(); });
});
</script>
