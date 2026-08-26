<div class="package-library--package">
	<input type="hidden" name="package" value="{$package->uri}">
	
	<div class="package-library--package-image{if $package->has_image} package-library--package-image--filled{/if}">
		<span data-avatar="{$package->name}" data-avatar-seed="{$package->uri}" data-avatar-icon="{$package->getIcon()}" data-avatar-color="{$package->getColor()}" data-avatar-text-color="{$package->getTextColor()}" data-avatar-ratio="16:9" data-avatar-size="135"{if $package->has_image} data-avatar-image="{devblocks_url}c=avatars&ctx=package&id={$package->id}{/devblocks_url}?v={$package->updated_at}"{/if}></span>
	</div>
	
	<div class="package-library--package-metadata">
		<div class="package-library--package-title">
			<b>{$package->name}</b>
		</div>
		<div class="package-library--package-description">
			{$package->description}
		</div>
		<div class="package-library--package-instructions">
			{$package->getInstructionsAsHtml() nofilter}
		</div>
	</div>
	
	<div class="package-library--package-prompts">
		{include file="devblocks:cerberusweb.core::configuration/section/package_import/prompts.tpl"}
	</div>
	
	<div class="package-library--package-buttons">
		<button type="button" data-cerb-action="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.create'|devblocks_translate|capitalize}</button>
		<button type="button" data-cerb-action="cancel">{'common.cancel'|devblocks_translate|capitalize}</button>
	</div>
</div>
