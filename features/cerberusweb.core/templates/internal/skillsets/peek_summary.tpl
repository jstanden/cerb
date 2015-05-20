{foreach from=$skillsets item=skillset}
<div>
<b>{$skillset->name}:</b>
<ul class="bubbles"> 
{foreach from=$skillset->skills item=skill key=skill_id}
	{if $skills.$skill_id}
	<li class="bubble-gray">
		<div style="width:25px;height:5px;background-color:rgb(200,200,200);display:inline-block;vertical-align:middle;">
			<div style="width:{$skills.$skill_id/100*25}px;height:5px;background-color:rgb(0,200,0);"></div>
		</div>
		{$skill->name}
	</li>
	{/if}
{/foreach}
</ul>
</div>
{foreachelse}
	{'common.none'|devblocks_translate|lower}
{/foreach}
