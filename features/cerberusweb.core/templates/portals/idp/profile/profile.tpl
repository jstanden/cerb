{include file="devblocks:cerberusweb.core::portals/idp/includes/header.tpl"}

<div class="cerb-portal-wrapper-narrow">
	<header style="text-align:right;margin-top:10px;">
		Logged in as <b>{$identity->username}</b> 
		[<a href="{devblocks_url}c=logout{/devblocks_url}">{'header.signoff'|devblocks_translate|lower}</a>]
	</header>

	<article class="cerb-portal-page-login cerb-portal-page--shadow">
		<header>
			<h1>{'common.profile'|devblocks_translate|capitalize}</h1>
		</header>
		
		{if !empty($error)}
		<div class="cerb-portal-form-error">
			<h1>{'common.error'|devblocks_translate|capitalize}</h1>
			<p>...</p>
		</div>
		{/if}
		
		<form action="{devblocks_url}c=profile&a=update{/devblocks_url}" method="POST" class="cerb-portal-form">
			<p>
				<label>{'common.email'|devblocks_translate|capitalize}</label>
				{$identity->getEmailAsString()}
			</p>
			
			<p>
				<label>{'dao.identity.given_name'|devblocks_translate|capitalize}</label>
				<input type="text" name="given_name" value="{$identity->given_name}">
			</p>
			
			<p>
				<label>{'dao.identity.nickname'|devblocks_translate|capitalize}</label>
				<input type="text" name="nickname" value="{$identity->nickname}">
			</p>
		
			<p>
				<label>{'dao.identity.middle_name'|devblocks_translate|capitalize}</label>
				<input type="text" name="middle_name" value="{$identity->middle_name}">
			</p>
			
			<p>
				<label>{'dao.identity.family_name'|devblocks_translate|capitalize}</label>
				<input type="text" name="family_name" value="{$identity->family_name}">
			</p>
			
			<p>
				<label>{'common.username'|devblocks_translate|capitalize}</label>
				<input type="text" name="username" value="{$identity->username}">
			</p>
			
			<p>
				<label>{'common.website'|devblocks_translate|capitalize}</label>
				<input type="text" name="website" value="{$identity->website}">
			</p>
			
			<p>
				<label>{'common.gender'|devblocks_translate|capitalize}</label>
				<span>
					<label><input type="radio" name="gender" value="M" {if 'M' == $identity->gender}checked="checked"{/if}> {'common.gender.male'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="gender" value="F" {if 'F' == $identity->gender}checked="checked"{/if}> {'common.gender.female'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="gender" value="" {if !$identity->gender}checked="checked"{/if}> {'common.gender.decline'|devblocks_translate|capitalize}</label>
				</span>
			</p>
		
			<p>
				<label>{'common.dob'|devblocks_translate|capitalize}</label>
				<input type="text" name="birthdate" value="{$identity->birthdate}">
			</p>
			
			<p>
				<label>{'common.timezone'|devblocks_translate|capitalize}</label>
				<select name="zoneinfo">
					<option value=""></option>
					{foreach from=$timezones item=tz}
					<option value="{$tz}" {if $identity->zoneinfo == $tz}selected="selected"{/if}>{$tz}</option>
					{/foreach}
				</select>
			</p>
		
			<p>
				<label>{'common.phone'|devblocks_translate|capitalize}</label>
				<input type="text" name="phone_number" value="{$identity->phone_number}">
			</p>
		
			<p>
				<label>{'common.password'|devblocks_translate|capitalize}</label>
				<input type="password" name="password" value="" autocomplete="off" spellcheck="false" {if $email}autofocus="autofocus"{/if}>
			</p>
			
			<button type="submit" class="cerb-button">{'common.update'|devblocks_translate|capitalize}</button>
		</form>
	</article>
</div>

{include file="devblocks:cerberusweb.core::portals/idp/includes/footer.tpl"}