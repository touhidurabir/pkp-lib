{**
 * templates/user/confirmPassword.tpl
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display page prompting user to confirm their password to access sensitive/restricted area of the application
 *}
{extends file="layouts/backend.tpl"}
{block name="page"}
	<script type="text/javascript">
		$(function() {ldelim}
			$('#confirmPassword').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
	</script>
	<h1 class="app__pageHeading text-lg-semibold text-primary">
		{translate key="user.confirmAccess"}
	</h1>
	<div class="app__contentPanel">

	<form class="pkp_form" id="confirmPassword" method="POST" action="{$submitUrl}">
		{csrf}
		<input hidden name="cancelUrl" value="{$cancelUrl|escape}" />
		<input hidden name="source" value="{$source|escape}" />
		<input hidden name="isActionRequest" value="{$isActionRequest|escape}"/>

		<p class="mb-2"><span>{translate key="user.confirmAccess.description" userFullName=$currentUser->getFullName()}</span></p>

		{fbvFormArea id="confirmPassword"}
		    {fbvFormSection }
			    {fieldLabel translate=true for=password key="user.password"}
			    {fbvElement type="text" required=true password=true id="password" maxlength="32" size=$fbvStyles.size.MEDIUM}
		    {/fbvFormSection}

			<div>
				<a class="pkp_button pkp_button_offset" type="button" href="{$cancelUrl|escape}">{translate key="common.cancel"}</a>
				<button class="pkp_button pkp_button_primary" type="submit">{translate key="form.submit"}</button>
			</div>
		{/fbvFormArea}
	</form>
</div>
{/block}
