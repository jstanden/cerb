{$is_editable = $active_worker->is_superuser || ($context == CerberusContexts::CONTEXT_WORKER && $active_worker->id == $context_id)} 

{$skillsets_all = DAO_Skillset::getAll()}
{$skillsets_available = $skillsets_all}
{$skillsets_linked = $skillsets}
{$skill_labels = [ 25 => '(Basic)', 50 => '(Intermediate)', 75 => '(Advanced)', 100 => '(Expert)' ] }

{$fieldsets_dom_id = uniqid()}

{if $is_editable}
<form id="frm{$fieldsets_dom_id}" style="margin-bottom:10px;">
	<button type="button" class="add"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
</form>
{/if}

<div id="{$fieldsets_dom_id}">

{if !empty($skillsets_linked)}
	{$skillsets_available = array_diff_key($skillsets_available, $skillsets_linked)}
	
	{foreach from=$skillsets_linked item=skillset}
	
		<fieldset class="peek cerb-skillset">
			<legend>{$skillset->name}</legend>
			
			<div style="padding-left:10px;">
			
			{foreach from=$skillset->skills item=skill}
			<div style="display:inline-block;width:250px;margin:0px 10px 10px 5px;">
				<label>{$skill->name} <small>{$skill_labels.{$skill->level}}</small></label>
				
				<div style="position:relative;display:inline-block;width:200px;height:10px;background-color:rgb(230,230,230);border-radius:10px;">
					<div style="position:relative;display:inline-block;width:{$skill->level/100*200}px;height:10px;background-color:rgb(92,156,204);border-radius:10px;">
					</div>
				</div>
			</div>
			{/foreach}
			
			</div>
		</fieldset>
		
	{/foreach}
{else}

<p>
	<b>There are no skills associated with this record.</b>
</p>

{/if}

</div>

{if $is_editable}
<script type="text/javascript">
$(function() {
	var $frm = $('#frm{$fieldsets_dom_id}');
	
	$frm.find('button.add').click(function() {
		var $skills_popup = genericAjaxPopup('chooser_skills', 'c=internal&a=handleSectionAction&section=skills&action=showSkillsChooserPopup&context={$context}&context_id={$context_id}', null, false, '650');
		
		$skills_popup.on('skills_save', function(e) {
			// Reload the skills section
			var $tabs = $frm.closest('div.ui-tabs');
			var tabId = $tabs.tabs("option", "active");
			$tabs.tabs("load", tabId);
		});
	});
});
</script>
{/if}