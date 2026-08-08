{$popup_id = "popup{uniqid()}"}
{* Nothing here is data entry -- the editor is read-only and the toggle only picks a view -- so the
   dialog's dirty tracker should ignore the whole subtree rather than arm a discard warning *}
<div id="{$popup_id}" data-cerb-ui-dialog-no-dirty>
	<div class="cerb-ui-form">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle" data-cerb-only-differences>
				<input type="checkbox" checked>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<span>Only show differences</span>

			<label class="cerb-ui-toggle cerb-u-ml-2" data-cerb-ignore-collation>
				<input type="checkbox">
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<span title="Collation drift is expected everywhere while the schema migrates off utf8mb3">Ignore collation</span>

			<button type="button" class="cerb-ui-button cerb-u-ml-auto" data-cerb-fold>
				<span class="cerb-icons cerb-icon-chevron-right"></span> Collapse
			</button>
			<button type="button" class="cerb-ui-button" data-cerb-unfold>
				<span class="cerb-icons cerb-icon-chevron-down"></span> Expand
			</button>
			<button type="button" class="cerb-ui-button" data-cerb-copy>
				<span class="cerb-icons cerb-icon-clipboard"></span> Copy
			</button>
		</div>

		<div data-cerb-empty="clean" hidden class="cerb-u-fgg-4">
			No differences &mdash; the database matches <code>cerb.schema.kata</code>.
		</div>
		<div data-cerb-empty="collation-only" hidden class="cerb-u-fgg-4">
			No structural differences &mdash; everything that drifted is collation.
		</div>

		<div class="cerb-ui-kataeditor" data-cerb-kata>
			<div class="cerb-ui-kataeditor--gutter" aria-hidden="true"></div>
			<div class="cerb-ui-kataeditor--field">
				<div class="cerb-ui-kataeditor--highlight" aria-hidden="true"></div>
				<textarea class="cerb-ui-kataeditor--input" data-editor-lines="24" data-editor-readonly spellcheck="false"></textarea>
				<span class="cerb-ui-kataeditor--caret-anchor"></span>
			</div>
		</div>

		{if $missing_tables}
			<div class="cerb-u-fgg-4">
				In <code>cerb.schema.kata</code> but not in this database:
				<b>{$missing_tables}</b>
			</div>
		{/if}
	</div>

	<button type="button" class="close"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.ok'|devblocks_translate}</button>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $div = $('#{$popup_id}');
	const $popup = genericAjaxPopupFind($div);

	// Every variant ships with the popup so the toggles never need a round trip
	const kata = {
		all: {$kata_all|json_encode nofilter},
		differences: {$kata_differences|json_encode nofilter},
		differences_no_collation: {$kata_differences_no_collation|json_encode nofilter}
	};

	const $editor_el = $div.find('[data-cerb-kata]');
	const $empty = $div.find('[data-cerb-empty]');

	$popup.one('popup_open', function() {
		$popup.dialog('option', 'title', 'Database Schema KATA');

		// Built after the dialog is open so the editor measures its line height against a laid-out box.
		// `readOnly` and the row count come from data-editor-readonly / data-editor-lines on the textarea.
		const editor = new CerbUI.KataEditor($editor_el[0], {
			minLines: 24
		});

		const render = function() {
			const only_differences = toggle_differences.getValue();
			const ignore_collation = toggle_collation.getValue();

			const text = !only_differences
				? kata.all
				: (ignore_collation ? kata.differences_no_collation : kata.differences)
				;

			const is_empty = only_differences && '' === text;

			// Empty with collation ignored doesn't mean the database matches -- it means the only drift was
			// collation, which is a different (and, mid-migration, expected) answer
			const collation_only = is_empty && '' !== kata.differences;

			editor.setValue(text);   // drops folds, which is what we want on a new document
			$editor_el.prop('hidden', is_empty);
			$empty.filter('[data-cerb-empty=\'clean\']').prop('hidden', !is_empty || collation_only);
			$empty.filter('[data-cerb-empty=\'collation-only\']').prop('hidden', !collation_only);

			// The full dump is the thing you paste back, so it always carries its collations
			toggle_collation.setDisabled(!only_differences);
		};

		const toggle_differences = new CerbUI.Toggle($div.find('[data-cerb-only-differences]')[0], {
			checked: true,
			onChange: render
		});

		const toggle_collation = new CerbUI.Toggle($div.find('[data-cerb-ignore-collation]')[0], {
			checked: false,
			onChange: render
		});

		render();

		$div.find('[data-cerb-fold]').on('click', function() {
			editor.foldToDepth(2);   // `tables:` open, one row per table -- foldAll() would leave a single line
		});

		$div.find('[data-cerb-unfold]').on('click', function() {
			editor.unfoldAll();
		});

		$div.find('[data-cerb-copy]').on('click', function() {
			// getValue() is the full document; the textarea only ever holds the folded projection
			navigator.clipboard.writeText(editor.getValue());
		});

		$div.find('button.close').on('click', function() {
			genericAjaxPopupClose($popup);
		});
	});
});
</script>
