<?php
class UmScAccountController extends Extension_UmScController {
	function isVisible() {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		return !empty($active_contact);
	}
	
	function renderSidebar(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		
		$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/sidebar_menu.tpl");
	}
	
	function writeResponse(DevblocksHttpResponse $response) {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$path = $response->path;
		
		@array_shift($path); // account
		
		// Scope
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null); /* @var $active_contact Model_Contact */
		
		@$module = array_shift($path);
		
		switch($module) {
			case 'password':
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/password/index.tpl");
				break;
				
			case 'sharing':
				$contact_addresses = $active_contact->getEmails();
				$tpl->assign('contact_addresses', $contact_addresses);
				
				if(is_array($contact_addresses)) {
					$shared_by = DAO_SupportCenterAddressShare::getSharedBy(array_keys($contact_addresses), false);
					$tpl->assign('shared_by_me', $shared_by);
					
					$shared_with = DAO_SupportCenterAddressShare::getSharedWith(array_keys($contact_addresses), false);
					$tpl->assign('shared_with_me', $shared_with);
				}
				
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/sharing/index.tpl");
				break;
				
			case 'delete':
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/delete/index.tpl");
				break;
				
			default:
			case 'profile':
				// Show fields
				if(null != ($show_fields = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'account.fields', null))) {
					$tpl->assign('show_fields', @json_decode($show_fields, true));
				}
				
				// Avatar
				if(false != ($avatar = DAO_ContextAvatar::getByContext(CerberusContexts::CONTEXT_CONTACT, $active_contact->id))) {
					$imagedata = 'data:' . $avatar->content_type . ';base64,' . base64_encode(Storage_ContextAvatar::get($avatar));
					$tpl->assign('imagedata', $imagedata);
				}
				
				$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/profile/index.tpl");
				break;
				
			case 'email':
				@$id = array_shift($path);
				
				if('confirm' == $id) {
					@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
					@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');
					
					if(empty($email))
						$email = $umsession->getProperty('account.email.add', '');
					
					$tpl->assign('email', $email);
					$tpl->assign('confirm', $confirm);
					
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/email/confirm.tpl");
				
				// Security check
				} elseif(empty($id) || null == ($address = DAO_Address::lookupAddress(urldecode(str_replace(array('_at_','_dot_'),array('%40','.'),$id)), false)) || $address->contact_id != $active_contact->id) {
					$addresses = $active_contact->getEmails();
					$tpl->assign('addresses', $addresses);
					
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/email/index.tpl");
					
				// Display all emails
				} else {
					// Addy
					$tpl->assign('address',$address);
			
					$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS, true, true);
					$tpl->assign('address_custom_fields', $address_fields);
			
					if(null != ($address_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ADDRESS, $address->id))
						&& is_array($address_field_values))
						$tpl->assign('address_custom_field_values', array_shift($address_field_values));
					
					// Org
					if(!empty($address->contact_org_id) && null != ($org = DAO_ContactOrg::get($address->contact_org_id))) {
						$tpl->assign('org',$org);
						
						$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG, true, true);
						$tpl->assign('org_custom_fields', $org_fields);
						
						if(null != ($org_field_values = DAO_CustomFieldValue::getValuesByContextIds(CerberusContexts::CONTEXT_ORG, $org->id))
							&& is_array($org_field_values))
							$tpl->assign('org_custom_field_values', array_shift($org_field_values));
					}
					
					// Show fields
					if(null != ($show_fields = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'account.fields', null))) {
						$tpl->assign('show_fields', @json_decode($show_fields, true));
					}
					
					$tpl->display("devblocks:cerberusweb.support_center:portal_".ChPortalHelper::getCode() . ":support_center/account/email/display.tpl");
				}
				
				break;
		}
	}
	
	function doProfileUpdateAction() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		if(null == $active_contact)
			return;
		
		$show_fields = array();
		if(null != ($show_fields = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'account.fields', null)))
			@$show_fields = json_decode($show_fields, true);
		
		$fields = array();
		
		// First name
		if(isset($show_fields['contact_first_name']) && $show_fields['contact_first_name'] == 2) {
			@$first_name = DevblocksPlatform::importGPC($_POST['first_name'],'string','');
			$fields[DAO_Contact::FIRST_NAME] = $first_name;
		}
		
		// Last name
		if(isset($show_fields['contact_last_name']) && $show_fields['contact_last_name'] == 2) {
			@$last_name = DevblocksPlatform::importGPC($_POST['last_name'],'string','');
			$fields[DAO_Contact::LAST_NAME] = $last_name;
		}
		
		// Title
		if(isset($show_fields['contact_title']) && $show_fields['contact_title'] == 2) {
			@$title = DevblocksPlatform::importGPC($_POST['title'],'string','');
			$fields[DAO_Contact::TITLE] = $title;
		}
		
		// Username
		if(isset($show_fields['contact_username']) && $show_fields['contact_username'] == 2) {
			@$username = DevblocksPlatform::importGPC($_POST['username'],'string','');
			$fields[DAO_Contact::USERNAME] = $username;
		}
		
		// Gender
		if(isset($show_fields['contact_gender']) && $show_fields['contact_gender'] == 2) {
			@$gender = DevblocksPlatform::importGPC($_POST['gender'],'string','');
			if(!in_array($gender, array('M','F')))
				$gender = '';
			
			$fields[DAO_Contact::GENDER] = $gender;
		}
		
		// Location
		if(isset($show_fields['contact_location']) && $show_fields['contact_location'] == 2) {
			@$location = DevblocksPlatform::importGPC($_POST['location'],'string','');
			$fields[DAO_Contact::LOCATION] = $location;
		}
		
		// DOB
		if(isset($show_fields['contact_dob']) && $show_fields['contact_dob'] == 2) {
			@$dob = DevblocksPlatform::importGPC($_POST['dob'],'string','');
			
			$dob_ts = null;
			
			if(!empty($dob) && false == ($dob_ts = strtotime($dob . ' 00:00 GMT')))
				$dob_ts = null;
		
			$fields[DAO_Contact::DOB] = (null == $dob_ts) ? null : gmdate('Y-m-d', $dob_ts);
		}
		
		// Phone
		if(isset($show_fields['contact_phone']) && $show_fields['contact_phone'] == 2) {
			@$phone = DevblocksPlatform::importGPC($_POST['phone'],'string','');
			$fields[DAO_Contact::PHONE] = $phone;
		}
		
		// Mobile
		if(isset($show_fields['contact_mobile']) && $show_fields['contact_mobile'] == 2) {
			@$mobile = DevblocksPlatform::importGPC($_POST['mobile'],'string','');
			$fields[DAO_Contact::MOBILE] = $mobile;
		}
		
		// Change the primary email if requested, but verify ownership
		@$primary_email_id = DevblocksPlatform::importGPC($_POST['primary_email_id'],'integer',0);
		if($primary_email_id && $primary_email_id != $active_contact->primary_email_id) {
			if(false != ($address = DAO_Address::get($primary_email_id)) && $address->contact_id == $active_contact->id) {
				$fields[DAO_Contact::PRIMARY_EMAIL_ID] = $primary_email_id;
			}
		}
		
		// Photo
		// [TODO] Do this as Ajax with a save button?
		if(isset($show_fields['contact_photo']) && $show_fields['contact_photo'] == 2) {
			@$imagedata = DevblocksPlatform::importGPC($_POST['imagedata'],'string','');
			DAO_ContextAvatar::upsertWithImage(CerberusContexts::CONTEXT_CONTACT, $active_contact->id, $imagedata);
		}
		
		if(!empty($fields)) {
			DAO_Contact::update($active_contact->id, $fields);
			
			// Update session
			$umsession->login(DAO_Contact::get($active_contact->id));
		}
	}

	function doEmailUpdateAction() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		
		if(null == $active_contact)
			return;
			
		@$action = DevblocksPlatform::importGPC($_POST['action'],'string','');
		@$id = DevblocksPlatform::importGPC($_POST['id'],'integer',0);
		
		if(!$id || false == ($address = DAO_Address::get($id)))
			return;
		
		switch($action) {
			case 'remove':
				// Can't remove primary email
				if($active_contact->primary_email_id != $address->id)
					DAO_Address::update($address->id, array(
						DAO_Address::CONTACT_ID => 0,
					));
				break;
				
			default:
				$customfields = DAO_CustomField::getAll();
				
				// Compare editable fields
				$show_fields = array();
				if(null != ($show_fields = DAO_CommunityToolProperty::get(ChPortalHelper::getCode(), 'account.fields', null)))
					@$show_fields = json_decode($show_fields, true);
				
				if(!empty($address)) {
					$contact_fields = array();
					$contact_customfields = array();
					$addy_fields = array();
					$addy_customfields = array();
					$org_fields = array();
					$org_customfields = array();
					
					if(isset($_POST['is_primary']) && !empty($_POST['is_primary'])) {
						$contact_fields[DAO_Contact::PRIMARY_EMAIL_ID] = $address->id;
						// [TODO] This could be done better
						$active_contact->primary_email_id = $address->id;
						$umsession->login($active_contact);
					}
					
					if(is_array($show_fields))
					foreach($show_fields as $field_name => $visibility) {
						if(2 != $visibility)
							continue;
						
						@$val = DevblocksPlatform::importGPC($_POST[$field_name],'string','');
							
						// Handle specific fields
						switch($field_name) {
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
					
					// Contact
					if(!empty($contact_fields))
						DAO_Contact::update($active_contact->id, $contact_fields);
					if(!empty($contact_customfields))
						DAO_CustomFieldValue::formatAndSetFieldValues(CerberusContexts::CONTEXT_CONTACT, $active_contact->id, $contact_customfields, true, false, false);
						
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
				break;
		}
				
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email')));
	}
	
	function doShareUpdateAction() {
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$contact_addresses = $active_contact->getEmails();

		$errors = array();

		try {
			// *** Handle shared by
			@$share_email = DevblocksPlatform::importGPC($_REQUEST['share_email'],'array','');

			if(is_array($share_email) && !empty($share_email)) {
				foreach($share_email as $idx => $share_id) {
					// Permissions
					if(!isset($contact_addresses[$share_id])) {
						unset($share_email[$idx]);
						continue;
					}
					
					@$share_emails = DevblocksPlatform::importGPC($_REQUEST['share_with_'.$share_id],'array',array());
					$share_with_ids = array();
					
					if(is_array($share_emails) && !empty($share_emails)) {
						$share_emails = array_unique($share_emails);
						
						foreach($share_emails as $email) {
							if(0==strlen(trim($email)))
								continue;
								
							// Lookup
							if(null !== ($lookup = DAO_Address::lookupAddress($email, false)) && $lookup->contact_id) {
								if(isset($contact_addresses[$lookup->id])) {
									$errors[] = sprintf("%s is your own email address.", $email);
								} else {
									$share_with_ids[] = $lookup->id;
								}
							} else {
								$errors[] = sprintf("%s is not registered here.", $email);
							}
						}
					}

					// Share
					if(!empty($share_with_ids))
						DAO_SupportCenterAddressShare::setSharedWith($share_id, $share_with_ids);
						
					// Delete omitted rows
					DAO_SupportCenterAddressShare::deleteWhereNotIn($share_id, $share_with_ids);
				}
			}

			// *** Handle shared with
			@$address_with_id = DevblocksPlatform::importGPC($_REQUEST['address_with_id'],'array','');
			@$address_from_id = DevblocksPlatform::importGPC($_REQUEST['address_from_id'],'array','');
			@$share_with_status = DevblocksPlatform::importGPC($_REQUEST['share_with_status'],'array','');
			
			if(is_array($address_with_id) && !empty($address_with_id)) {
				foreach($address_with_id as $idx => $with_id) {
					if(!isset($address_with_id[$idx]) || !isset($share_with_status[$idx]))
						continue;
						
					// Authorize
					if(!isset($contact_addresses[$with_id])) {
						unset($address_with_id[$idx]);
						unset($address_from_id[$idx]);
						unset($share_with_status[$idx]);
						continue;
					}
					
					// Delete
					if(2 == $share_with_status[$idx]) {
						DAO_SupportCenterAddressShare::delete($address_from_id[$idx], $with_id);
						
					// Show|Hide
					} else {
						$fields = array(
							DAO_SupportCenterAddressShare::IS_ENABLED => $share_with_status[$idx] ? 1 : 0,
						);
						$where = sprintf("%s = %d AND %s = %d",
							DAO_SupportCenterAddressShare::SHARE_ADDRESS_ID,
							$address_from_id[$idx],
							DAO_SupportCenterAddressShare::WITH_ADDRESS_ID,
							$with_id
						);
						DAO_SupportCenterAddressShare::updateWhere($fields, $where);
					}
				}
			}
			
			if(!empty($errors))
				$tpl->assign('error', implode('<br>', $errors));
			
		} catch(Exception $e) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('error', $e->getMessage());
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','sharing')));
	}
	
	function doPasswordUpdateAction() {
		@$change_password = DevblocksPlatform::importGPC($_REQUEST['change_password'],'string','');
		@$change_password2 = DevblocksPlatform::importGPC($_REQUEST['change_password2'],'string','');
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$url_writer = DevblocksPlatform::services()->url();
		$tpl = DevblocksPlatform::services()->templateSandbox();

		try {
			if(empty($active_contact) || empty($active_contact->id))
				throw new Exception("Your session is invalid.");
			
			if(empty($change_password) || empty($change_password2))
				throw new Exception("Your password cannot be blank.");
			
			if(0 != strcmp($change_password, $change_password2))
				throw new Exception("Your passwords do not match.");
				
			// Change password?
			$salt = CerberusApplication::generatePassword(8);
			$fields = array(
				DAO_Contact::AUTH_SALT => $salt,
				DAO_Contact::AUTH_PASSWORD => md5($salt.md5($change_password)),
			);
			DAO_Contact::update($active_contact->id, $fields);
			
			$tpl->assign('success', true);
			
		} catch(Exception $e) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('error', $e->getMessage());
			
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','password')));
	}
	
	function doEmailAddAction() {
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$url_writer = DevblocksPlatform::services()->url();
		
		try {
			if(null == $active_contact)
				return;
	
			@$add_email = DevblocksPlatform::strLower(DevblocksPlatform::importGPC($_REQUEST['add_email'],'string',''));
			
			// Validate
			$address_parsed = imap_rfc822_parse_adrlist($add_email,'host');
			if(empty($add_email) || empty($address_parsed) || !is_array($address_parsed) || empty($address_parsed[0]->host) || $address_parsed[0]->host=='host')
				throw new Exception("The email address you provided is invalid.");
	
			// Is this address already assigned
			if(null != ($address = DAO_Address::lookupAddress($add_email, false))) {
				if(!empty($address->contact_id))
					throw new Exception("The email address you provided is already assigned to an account.");
				
				// [TODO] Or awaiting confirmation
				
				if($address->is_banned)
					throw new Exception("The email address you provided is not available.");
			}
			
			// If available, send confirmation email w/ link
			$fields = array(
				DAO_ConfirmationCode::CONFIRMATION_CODE => CerberusApplication::generatePassword(8),
				DAO_ConfirmationCode::NAMESPACE_KEY => 'support_center.email.confirm',
				DAO_ConfirmationCode::META_JSON => json_encode(array(
					'contact_id' => $active_contact->id,
					'email' => $add_email,
				)),
				DAO_ConfirmationCode::CREATED => time(),
			);
			DAO_ConfirmationCode::create($fields);

			$umsession->setProperty('account.email.add', $add_email);
			
			// Quick send
			$msg = sprintf(
				"Confirmation code: %s",
				urlencode($fields[DAO_ConfirmationCode::CONFIRMATION_CODE])
			);
			CerberusMail::quickSend($add_email,"Please confirm your email address", $msg);
			
		} catch (Exception $e) {
			$tpl->assign('error', $e->getMessage());

			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email','confirm')));
		//DevblocksPlatform::redirect(new DevblocksHttpResponse(array('account','email')));
		//exit;
	}
	
	function doEmailConfirmAction() {
		@$email = DevblocksPlatform::importGPC($_REQUEST['email'],'string','');
		@$confirm = DevblocksPlatform::importGPC($_REQUEST['confirm'],'string','');

		// Verify session
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);

		try {
			if(null == $active_contact)
				throw new Exception("Your session has expired.");
			
			// Lookup code
			if(null == ($code = DAO_ConfirmationCode::getByCode('support_center.email.confirm', $confirm)))
				throw new Exception("Your confirmation code is invalid.");
				
			// Compare session
			if(!isset($code->meta['contact_id']) || $active_contact->id != $code->meta['contact_id'])
				throw new Exception("Your confirmation code is invalid.");
				
			// Compare email addy
			if(!isset($code->meta['email'])
				|| null == ($address = DAO_Address::lookupAddress($code->meta['email'], true))
				|| 0 != strcasecmp($code->meta['email'],$email)
				|| 0 != strcasecmp($address->email,$email)
				)
				throw new Exception("Your email address could not be registered.");
				
			// Pass + associate
			DAO_ConfirmationCode::delete($code->id);
			DAO_Address::update($address->id,array(
				DAO_Address::CONTACT_ID => $active_contact->id,
			));
			
			$address_uri = urlencode(str_replace(array('@','.'),array('_at_','_dot_'),$address->email));
			
			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email',$address_uri)));
			return;
			
		} catch(Exception $e) {
			$tpl = DevblocksPlatform::services()->templateSandbox();
			$tpl->assign('error', $e->getMessage());

			DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email','confirm')));
			return;
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','email')));
	}
	
	function doDeleteAction() {
		@$captcha = DevblocksPlatform::importGPC($_REQUEST['captcha'], 'string', '');
		
		$umsession = ChPortalHelper::getSession();
		$active_contact = $umsession->getProperty('sc_login', null);
		$tpl = DevblocksPlatform::services()->templateSandbox();
		$url_writer = DevblocksPlatform::services()->url();
		
		try {
			// Load the contact account
			if(null == $active_contact)
				throw new Exception("Your request could not be processed at this time.");
				
			// Compare the captcha
			$compare_captcha = $umsession->getProperty('write_captcha', '');
			if(0 != strcasecmp($captcha, $compare_captcha))
				throw new Exception("Your text did not match the image.");
				
			// Delete the contact account
			DAO_Contact::delete($active_contact->id);
			unset($active_contact);
				
			// Clear the session
			$umsession->destroy();
			
			// Response
			header("Location: " . $url_writer->write('', true));
			exit;
			
		} catch(Exception $e) {
			$tpl->assign('error', $e->getMessage());
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('portal',ChPortalHelper::getCode(),'account','delete')));
	}
	
	function configure(Model_CommunityTool $portal) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('portal', $portal);

		if(null != ($show_fields = DAO_CommunityToolProperty::get($portal->code, 'account.fields', null))) {
			$tpl->assign('show_fields', @json_decode($show_fields, true));
		}
		
		$address_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ADDRESS, true, true);
		$tpl->assign('address_custom_fields', $address_fields);

		$org_fields = DAO_CustomField::getByContext(CerberusContexts::CONTEXT_ORG, true, true);
		$tpl->assign('org_custom_fields', $org_fields);
		
		$types = Model_CustomField::getTypes();
		$tpl->assign('field_types', $types);
		
		$tpl->display("devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/account.tpl");
	}
	
	function saveConfiguration(Model_CommunityTool $portal) {
		@$aFields = DevblocksPlatform::importGPC($_POST['fields'],'array',array());
		@$aFieldsVisible = DevblocksPlatform::importGPC($_POST['fields_visible'],'array',array());

		$fields = array();
		
		if(is_array($aFields))
		foreach($aFields as $idx => $field) {
			$mode = $aFieldsVisible[$idx];
			if(!is_null($mode))
				$fields[$field] = intval($mode);
		}
		
		DAO_CommunityToolProperty::set($portal->code, 'account.fields', json_encode($fields));
	}
};