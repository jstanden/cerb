{*
	Reusable inline template field: a CerbUI.ScriptingEditor (Twig) with a placeholder-insert menu and a
	built-in tester — the inline replacement for the cerbTemplateTrigger() popup. Params:
	  name         POST field name for the template content (required)
	  value        current template text
	  context      record-type context(s) for placeholders/tester — alias or csv for composite (e.g. "worker")
	  key_prefix   optional token key prefix (default "")
	  placeholders optional assoc array of EXTRA tokens (key => label) not part of the context
	  lines        editor height in rows (default 3)
	  gutter       show the line-number gutter (default true; pass false for short one/two-line fields)
	  placeholder  textarea placeholder text (optional)
	The tester posts to profiles/snippet/test against the FIRST context (sample/random record).
	After init the wrapper element exposes `el.cerbTemplateField = { editor, setContext(ctx) }` so callers
	with a dynamic record type (e.g. search_index) can repoint the placeholders/tester.
*}
{$tf_id = "tplfield_{uniqid()}"}
{$tf_lines = $lines|default:3}
{$tf_key_prefix = $key_prefix|default:''}
{$tf_gutter = true}
{if isset($gutter)}{$tf_gutter = $gutter}{/if}
{$tf_primary_context = $context}
{if strpos($context, ',') !== false}{$tf_primary_context = $context|substr:0:strpos($context, ',')}{/if}

<div class="cerb-ui-template-field" id="{$tf_id}" data-context="{$context}" data-key-prefix="{$tf_key_prefix}" data-primary-context="{$tf_primary_context}">
	<div class="cerb-ui-toolbar-strip">
		<button type="button" class="cerb-ui-toolbar-button" data-cerb-template-insert><span class="cerb-icons cerb-icon-placeholders"></span> {'common.placeholders'|devblocks_translate|capitalize}</button>
		<span class="cerb-ui-toolbar-divider"></span>
		<button type="button" class="cerb-ui-toolbar-button" data-cerb-template-test><span class="cerb-icons cerb-icon-play"></span> {'common.test'|devblocks_translate|capitalize}</button>
		<button type="button" class="cerb-ui-toolbar-button" data-cerb-template-help title="{'common.help'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-question-mark"></span></button>
	</div>

	<textarea name="{$name}" data-editor-lines="{$tf_lines}" spellcheck="false"{if !empty($placeholder)} placeholder="{$placeholder}"{/if}>{$value}</textarea>

	<div data-cerb-template-test-results class="cerb-u-mt-2"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $field = $('#{$tf_id}');
	const fieldEl = $field[0];
	const editor = new CerbUI.ScriptingEditor($field.find('textarea')[0], { gutter: {if $tf_gutter}true{else}false{/if} });
	const $insertBtn = $field.find('[data-cerb-template-insert]');

	// Extra (non-context) placeholder tokens this field accepts, keyed by token name with a label value.
	const extraPlaceholders = {if !empty($placeholders)}{$placeholders|json_encode nofilter}{else}{literal}{}{/literal}{/if};

	// Context can change at runtime (setContext) — keep these mutable and read live in the tester.
	let context = $field.attr('data-context');
	let primaryContext = $field.attr('data-primary-context');
	const keyPrefix = $field.attr('data-key-prefix');

	const insertToken = function(token) {
		if(null == token) return;
		editor.insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
		editor.focus();
	};

	// (Re)load the placeholder-insert menu for the current context. The tree is context-specific, so a
	// context change tears down the old CerbUI.Menu + <ul> and rebuilds. CerbUI.Menu's clickTrigger owns
	// the open/close toggle on the Insert button.
	let menuUl = null;
	const loadPlaceholderMenu = function() {
		if(menuUl) {
			const inst = (window.CerbUI && CerbUI.Menu) ? CerbUI.Menu.from(menuUl) : null;
			if(inst) inst.destroy();
			$(menuUl).remove();
			menuUl = null;
		}

		let args = 'c=internal&a=invoke&module=records&action=templatePlaceholders'
			+ '&context=' + encodeURIComponent(context)
			+ '&key_prefix=' + encodeURIComponent(keyPrefix);

		for(const k in extraPlaceholders)
			args += '&placeholders[' + encodeURIComponent(k) + ']=' + encodeURIComponent(extraPlaceholders[k]);

		genericAjaxGet('', args, function(html) {
			const $menu = $(html);
			$field.append($menu);
			menuUl = $menu[0];

			if(window.CerbUI && CerbUI.Menu) {
				new CerbUI.Menu(menuUl, {
					clickTrigger: $insertBtn[0],
					selectableParents: true,
					filter: true,
					onSelect: function(li, src) {
						insertToken(src.getAttribute('data-token'));
					}
				});
			}
		});
	};

	loadPlaceholderMenu();

	// Built-in tester: render the template with the primary context's sample record. Extra placeholders
	// are passed as keys so they echo as their own literal token instead of erroring.
	$field.find('[data-cerb-template-test]').on('click', function(e) {
		e.stopPropagation();

		const $results = $field.find('[data-cerb-template-test-results]');

		const formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'snippet');
		formData.set('action', 'test');
		formData.set('snippet_context', primaryContext);
		formData.set('snippet_key_prefix', keyPrefix);
		formData.set('snippet_field', 'content');
		formData.set('content', editor.getValue());

		for(const k in extraPlaceholders)
			formData.append('placeholder_keys[]', k);

		genericAjaxPost(formData, $results, null);
	});

	// Help: the shared snippet/template help popup.
	$field.find('[data-cerb-template-help]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('help', 'c=profiles&a=invoke&module=snippet&action=helpPopup', { my:'left top', at:'left+20 top+20' }, false, '50%');
	});

	// Dynamic-context API for callers that change the record type after render (e.g. search_index).
	fieldEl.cerbTemplateField = {
		editor: editor,
		setContext: function(ctx) {
			if(null == ctx || ctx === context) return;
			context = ctx;
			primaryContext = (ctx.indexOf(',') >= 0) ? ctx.split(',')[0] : ctx;
			$field.attr('data-context', context).attr('data-primary-context', primaryContext);
			loadPlaceholderMenu();
		}
	};
});
</script>
