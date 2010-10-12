<?php
class UmScAccountController extends Extension_UmScController {
	
	function isVisible() {
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		return !empty($active_user);
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::getTemplateService();
		
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		// Addy
		$address = DAO_Address::get($active_user->id);
		$tpl->assign('address',$address);

		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('address_custom_fields', $address_fields);

		if(null != ($address_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id))
			&& is_array($address_field_values))
			$tpl->assign('address_custom_field_values', array_shift($address_field_values));
		
		// Org
		if(!empty($address->contact_org_id) && null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
			$tpl->assign('org',$org);
			
			$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
			$tpl->assign('org_custom_fields', $org_fields);
			
			if(null != ($org_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org->id))
				&& is_array($org_field_values))
				$tpl->assign('org_custom_field_values', array_shift($org_field_values));
		}
		
		// Show fields		
		if(null != ($show_fields = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), 'account.fields', null))) {
			$tpl->assign('show_fields', @json_decode($show_fields, true));
		}

		// Login handler
        $login_handler = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), UmScApp::PARAM_LOGIN_HANDLER, '');
		$tpl->assign('login_handler', $login_handler);
		
		$tpl->display("devblocks:cerberusweb.support_center:portal_".UmPortalHelper::getCode() . ":support_center/account/index.tpl");
	}

	function saveAccountAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$umsession = UmPortalHelper::getSession();
		$active_user = $umsession->getProperty('sc_login', null);
		
		if(null == ($address = DAO_Address::get($active_user->id)))
			return;
		
		$customfields = DAO_CustomField::getAll();
		
		// Compare editable fields
		$show_fields = array();
		if(null != ($show_fields = DAO_CommunityToolProperty::get(UmPortalHelper::getCode(), 'account.fields', null)))
			@$show_fields = json_decode($show_fields, true);
		
		if(!empty($address)) {
			$addy_fields = array();
			$addy_customfields = array();
			$org_fields = array();
			$org_customfields = array();
			
			if(is_array($show_fields))
			foreach($show_fields as $field_name => $visibility) {
				if(2 != $visibility)
					continue;
				
				@$val = DevblocksPlatform::importGPC($_POST[$field_name],'string','');
					
				// Handle specific fields
				switch($field_name) {
					// Addys
					case 'addy_first_name':
						$addy_fields[DAO_Address::FIRST_NAME] = $val;
						break;
					case 'addy_last_name':
						$addy_fields[DAO_Address::LAST_NAME] = $val;
						break;
						
					// Orgs
					case 'org_name':
						if(!empty($val))
							$org_fields[DAO_ContactOrg::NAME] = $val; 
						break;
					case 'org_street':
						$org_fields[DAO_ContactOrg::STREET] = $val;
						break;
					case 'org_city':
						$org_fields[DAO_ContactOrg::CITY] = $val;
						break;
					case 'org_province':
						$org_fields[DAO_ContactOrg::PROVINCE] = $val;
						break;
					case 'org_postal':
						$org_fields[DAO_ContactOrg::POSTAL] = $val;
						break;
					case 'org_country':
						$org_fields[DAO_ContactOrg::COUNTRY] = $val;
						break;
					case 'org_phone':
						$org_fields[DAO_ContactOrg::PHONE] = $val;
						break;
					case 'org_website':
						$org_fields[DAO_ContactOrg::WEBSITE] = $val;
						break;
						
					// Custom fields
					default:
						// Handle array posts
						if(isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
							$val = array();
							foreach(array_keys($_POST[$field_name]) as $idx)
								$val[] = DevblocksPlatform::importGPC($_POST[$field_name][$idx], 'string', '');
						}
						
						// Address
						if('addy_custom_'==substr($field_name,0,12)) {
							$field_id = intval(substr($field_name,12));
							$addy_customfields[$field_id] = $val;
							
						// Org
						} elseif('org_custom_'==substr($field_name,0,11)) {
							$field_id = intval(substr($field_name,11));
							$org_customfields[$field_id] = $val;
						}
						break;
				}
			}
			
			// Change password?
			@$change_password = DevblocksPlatform::importGPC($_REQUEST['change_password'],'string','');
			@$change_password2 = DevblocksPlatform::importGPC($_REQUEST['change_password2'],'string','');
			if(!empty($change_password) && 0 == strcmp($change_password, $change_password2)) {
				$addy_fields[DAO_Address::PASS] = md5($change_password);
			}
			
			// Addy
			if(!empty($addy_fields))
				DAO_Address::update($address->id, $addy_fields);
			if(!empty($addy_customfields))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ADDRESS, $address->id, $addy_customfields, true, false, false);
			
			// Org
			if(!empty($org_fields) && !empty($address->contact_org_id))
				DAO_ContactOrg::update($address->contact_org_id, $org_fields);
			if(!empty($org_customfields) && !empty($address->contact_org_id))
				DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_ORG, $address->contact_org_id, $org_customfields, true, false, false);
		}
		
		$tpl->assign('account_success', true);
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',UmPortalHelper::getCode(),'account')));
	}
	
	function configure(Model_CommunityTool $instance) {
		$tpl = DevblocksPlatform::getTemplateService();

		if(null != ($show_fields = DAO_CommunityToolProperty::get($instance->code, 'account.fields', null))) {
			$tpl->assign('show_fields', @json_decode($show_fields, true));
		}
		
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS);
		$tpl->assign('address_custom_fields', $address_fields);

		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG);
		$tpl->assign('org_custom_fields', $org_fields);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('field_types', $types);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/config/module/account.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $instance) {
        @$aFields = DevblocksPlatform::importGPC($_POST['fields'],'array',array());
        @$aFieldsVisible = DevblocksPlatform::importGPC($_POST['fields_visible'],'array',array());

        $fields = array();
        
        if(is_array($aFields))
        foreach($aFields as $idx => $field) {
        	$mode = $aFieldsVisible[$idx];
        	if(!is_null($mode))
        		$fields[$field] = intval($mode);
        }
        
        DAO_CommunityToolProperty::set($instance->code, 'account.fields', json_encode($fields));
	}	
};