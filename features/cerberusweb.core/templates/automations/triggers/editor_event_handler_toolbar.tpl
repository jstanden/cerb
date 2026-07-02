{* Integrated-toolbar variant of editor_event_handler_buttons.tpl: the Placeholders + Test toggles as
   cerb-ui-toolbar <li>s, merged into a KataEditor's built-in toolbar via `toolbar.sections`. The host's onAction
   toggles the `[data-cerb-event-placeholders]` / `[data-cerb-event-tester]` panels (data-toggle drives the pressed
   state); `cerbCodeEditorToolbarEventHandler` (called on the fieldset) still inits the tester editor + run button. *}
<ul class="cerb-ui-toolbar" data-cerb-event-toolbar hidden>
	<li data-value="placeholders" data-toggle data-key="placeholders" data-icon="placeholders" title="{'common.placeholders'|devblocks_translate|capitalize}"></li>
	<li data-value="tester" data-toggle data-key="tester" data-icon="lab" title="{'common.test'|devblocks_translate|capitalize}"></li>
</ul>
