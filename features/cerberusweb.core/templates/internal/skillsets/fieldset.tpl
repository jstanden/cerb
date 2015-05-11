{$div_fieldset = uniqid()}
{$skill_labels = [ 25 => '(Basic)', 50 => '(Intermediate)', 75 => '(Advanced)', 100 => '(Expert)' ] }

<fieldset id="{$div_fieldset}" class="peek cerb-skillset">
	<legend>{$skillset->name}</legend>
	
	<div style="padding-left:10px;">
	
	{foreach from=$skillset->skills item=skill}
	<div class="cerb-skill">
		<label>{$skill->name} <small>{$skill_labels.{$skill->level}}</small></label>
		<input type="hidden" name="skill[{$skill->id}]" value="{$skill->level|default:0}" data-skill-id="{$skill->id}">
		<div class="skill-slider"></div>
	</div>
	{/foreach}
	
	</div>
</fieldset>

<script type="text/javascript">
$(function() {
	$('#{$div_fieldset}').find('div.skill-slider').each(function() {
		var $this = $(this);
		var $fieldset = $(this).closest('fieldset');
		var $input = $this.siblings('input:hidden');
		var $label = $this.siblings('label');
		var $skill_level = $label.find('small');
		
		$this.slider({
			disabled: false,
			value: $input.val(),
			min: 0,
			max: 100,
			step: 25,
			range: 'min',
			slide: function(event, ui) {
				switch(ui.value) {
					case 0:
						$skill_level.html('');
						break;
					case 25:
						$skill_level.html('{$skill_labels.25}');
						break;
					case 50:
						$skill_level.html('{$skill_labels.50}');
						break;
					case 75:
						$skill_level.html('{$skill_labels.75}');
						break;
					case 100:
						$skill_level.html('{$skill_labels.100}');
						break;
				}
			},
			stop: function(event, ui) {
				$input.val(ui.value);
				var skill_id = $input.attr('data-skill-id');
				
				// Save the changes via Ajax
				genericAjaxGet('', 'c=internal&a=handleSectionAction&section=skills&action=setContextSkill&context={$context}&context_id={$context_id}&skill_id=' + skill_id + '&level=' + ui.value);
			}
		});
	});
});
</script>
