<div class="package-library--container">
	<div class="package-library--package-chooser">
		<div class="package-library--package-container">
			<input type="text" class="package-library--package-search input_search" spellcheck="false">
			
			{foreach from=$packages item=point_packages key=point}
				{foreach from=$point_packages item=package}
				<div class="package-library--package" data-cerb-package="{$package->uri}">
					<div class="package-library--package-image{if $package->has_image} package-library--package-image--filled{/if}">
						<span data-avatar="{$package->name}" data-avatar-seed="{$package->uri}" data-avatar-icon="{$package->getIcon()}" data-avatar-ratio="16:9" data-avatar-size="135"{if $package->has_image} data-avatar-image="{devblocks_url}c=avatars&ctx=package&id={$package->id}{/devblocks_url}?v={$package->updated_at}"{/if}></span>
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
