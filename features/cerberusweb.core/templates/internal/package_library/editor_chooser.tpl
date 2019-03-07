<div class="package-library--container">
	<div class="package-library--package-chooser">
		<div class="package-library--package-container">
			<input type="text" class="package-library--package-search input_search" spellcheck="false">
			
			{foreach from=$packages item=point_packages key=point}
				{foreach from=$point_packages item=package}
				<div class="package-library--package" data-cerb-package="{$package->uri}">
					<div class="package-library--package-image">
						<img src="{devblocks_url}c=avatars&ctx=package&id={$package->id}{/devblocks_url}?v={$package->updated_at}" width="240" height="135">
					</div>
					<div class="package-library--package-title">
						<b>{$package->name}</b>
					</div>
					<div class="package-library--package-description">
						{$package->description}
					</div>
				</div>
				{/foreach}
			{/foreach}
		</div>
	</div>
	
	<div class="package-library--package-info"></div>
</div>
