{$form_skills_dom_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" method="post" id="{$form_skills_dom_id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="skills">
<input type="hidden" name="action" value="saveSkillsForContext">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

{include file="devblocks:cerberusweb.core::internal/skillsets/fieldsets_and_menu.tpl" skill_labels=$skill_labels skillsets_linked=$skillsets}

<div style="margin-top:10px;">
	<div class="status"></div>

	<button class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_skills_dom_id}');
	var $status = $frm.find('div.status');
	
	$frm.find('button.submit').click(function() {
			genericAjaxPost($frm, null, null, function() {
				
				Devblocks.showSuccess($status, 'Saved!', true, true);
			});
	});
	
});
</script>