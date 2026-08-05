{$uniqid = uniqid('iconBuilder')}
<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Icon Builder</div>
		<div class="cerb-ui-header--subtitle">Design a <code>cerb-icons</code> glyph and preview it across the UI. Edit only the inner SVG geometry; the 24&times;24 viewBox and outer stroke/fill wrapper are enforced. Copy the generated lines into <code>cerb-icons.scss</code> and <code>getCerbIcons()</code>, then <code>composer build-css</code>.</div>
	</div>
</div>

<div id="{$uniqid}"></div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	new CerbUI.IconBuilder(document.getElementById('{$uniqid}'), {
		agentToolbarHtml: {$agent_toolbar_html_json nofilter}
	});
});
</script>
