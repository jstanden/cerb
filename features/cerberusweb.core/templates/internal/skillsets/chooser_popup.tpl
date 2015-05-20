<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSkillsChooser">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="skills">
<input type="hidden" name="action" value="saveSkillsForContext">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

{include file="devblocks:cerberusweb.core::internal/skillsets/fieldsets_and_menu.tpl" skill_labels=$skill_labels skillsets_linked=$skillsets}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
var $popup = genericAjaxPopupFind('#frmSkillsChooser');

$popup.one('popup_open', function(event,ui) {
	$popup.dialog('option','title',"{'common.skills'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	
	$popup.find('button.submit').click(function() {
			genericAjaxPost('frmSkillsChooser', null, null, function() {
				
				// Trigger event
				var $event = jQuery.Event('skills_save');
				var $layer = $popup.attr('id').substring(5);
				genericAjaxPopupClose($layer, $event);
			});
	});
});
</script>