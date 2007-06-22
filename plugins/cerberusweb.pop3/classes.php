<?php
/*
 * [JAS]: [TODO] This really belongs in CORE, though it was a good
 * plugin exercise.
 */
class Pop3Cron extends CerberusCronPageExtension {
    function run() {
        if (!extension_loaded("imap")) die("IMAP Extension not loaded!");
        @set_time_limit(0); // Unlimited (if possible)
        @ini_set('memory_limit','64M');

        $mail = DevblocksPlatform::getMailService();
        $accounts = DAO_Mail::getPop3Accounts(); /* @var $accounts CerberusPop3Account[] */

        $timeout = ini_get('max_execution_time');
        $max_downloads = ($timeout) ? 20 : 50; // [TODO] Make a job setting?
        
        foreach ($accounts as $account) { /* @var $account CerberusPop3Account */
            if(!$account->enabled)
                continue;
            
            echo 'Account being parsed is ', $account->nickname, '<br>';
            echo 'Time Limit: ', (($timeout) ? $timeout : 'unlimited') ,' secs<br>';
            echo 'Memory Limit: ', ini_get('memory_limit') ,'<br>';
            	
            switch($account->protocol) {
                default:
                case 'pop3': // 110
                    $connect = sprintf("{%s:%d/pop3/notls}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
                     
                case 'pop3-ssl': // 995
                    $connect = sprintf("{%s:%d/pop3/ssl/novalidate-cert}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
                     
                case 'imap': // 143
                    $connect = sprintf("{%s:%d/notls}INBOX",
                    $account->host,
                    $account->port
                    );
                    break;
            }

            $runtime = microtime(true);
             
            $mailbox = imap_open($connect,
            !empty($account->username)?$account->username:"",
            !empty($account->password)?$account->password:"")
            or die("Failed with error: ".imap_last_error());
            	
            $messages = array();
            $check = imap_check($mailbox);
            	
            // [TODO] Make this an account setting?
            $total = min($max_downloads,$check->Nmsgs);
            	
            //			$info = imap_fetch_overview($mailbox,"1:$total",0);
            //			print_r($info);echo "<BR>";
            	
            echo 'Init time: ',((microtime(true)-$runtime)*1000),' ms<br>';

            echo "<BR>";
            flush();

            $runtime = microtime(true);

            for($i=1;$i<=$total;$i++) {
                //			foreach($info as $msg) {
                /*
                * [TODO] Logic for max message size (>1MB, etc.) handling.  If over a
                * threshold then use the attachment parser (imap_fetchstructure) to toss
                * non-plaintext until the message fits.
                */
                 
                //	            $msgno = $msg->msgno;
                $msgno = $i;
                echo "<b>Downloading message ",$msgno,"</b> ";
                flush();
                 
                $time = microtime(true);
                 
                //			    $struct = imap_fetchstructure($mailbox, $i);
                //			    echo "Length: ",$msg->size,"<BR>";
                 
                $headers = imap_fetchheader($mailbox, $msgno);
                //				print_r($headers);

                $body = imap_body($mailbox, $msgno);
                //                $runtime_bytes += strlen($body);
                //				echo "Length: ",strlen($body),"<BR>";

                do {
                    $filename = APP_MAIL_PATH . 'new' . DIRECTORY_SEPARATOR . CerberusMail::generateMessageFilename();
                } while(file_exists($filename));
                $fp = fopen($filename,'w');

                if($fp) {
                    fwrite($fp,$headers,strlen($headers));
                    fwrite($fp,"\r\n\r\n");
                    fwrite($fp,$body,strlen($body));
                    @fclose($fp);
                }

                unset($headers);
                unset($body);

                $time = microtime(true) - $time;
                echo "(",sprintf("%d",($time*1000))," ms)<br>";
                flush();
                continue;
            }
            	
            imap_close($mailbox);
            	
            echo "<b>Total Runtime:</b> ",((microtime(true)-$runtime)*1000)," ms<br>";
        }
    }
    
    function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(__FILE__) . '/templates/';
		$tpl->assign('path', $tpl_path);
        
		$pop3_accounts = DAO_Mail::getPop3Accounts();
		$tpl->assign('pop3_accounts', $pop3_accounts);
		
		$tpl->display($tpl_path . 'cron/pop3/config.tpl.php');
    }
    
    function saveConfigurationAction() {
		@$ar_ids = DevblocksPlatform::importGPC($_POST['account_id'],'array');
		@$ar_enabled = DevblocksPlatform::importGPC($_POST['pop3_enabled'],'array');
		@$ar_nickname = DevblocksPlatform::importGPC($_POST['nickname'],'array');
		@$ar_protocol = DevblocksPlatform::importGPC($_POST['protocol'],'array');
		@$ar_host = DevblocksPlatform::importGPC($_POST['host'],'array');
		@$ar_username = DevblocksPlatform::importGPC($_POST['username'],'array');
		@$ar_password = DevblocksPlatform::importGPC($_POST['password'],'array');
		@$ar_port = DevblocksPlatform::importGPC($_POST['port'],'array');
		@$ar_delete = DevblocksPlatform::importGPC($_POST['delete'],'array');
		
		if(!is_array($ar_ids))
		    return;
		    
		foreach($ar_ids as $idx => $id) {
		    $nickname = $ar_nickname[$idx];
		    $protocol = $ar_protocol[$idx];
		    $host = $ar_host[$idx];
		    $username = $ar_username[$idx];
		    $password = $ar_password[$idx];
		    $port = $ar_port[$idx];
		    
			if(empty($nickname)) $nickname = "No Nickname";
			
			// Defaults
			if(empty($port)) {
			    switch($protocol) {
			        case 'pop3':
			            $port = 110; 
			            break;
			        case 'pop3-ssl':
			            $port = 995;
			            break;
			        case 'imap':
			            $port = 143;
			            break;
			    }
			}
			
			if(!empty($id) && is_numeric(array_search($id, $ar_delete))) {
				DAO_Mail::deletePop3Account($id);
				
			} elseif(!empty($id)) {
			    $enabled = (is_array($ar_enabled) && is_numeric(array_search($id, $ar_enabled))) ? 1 : 0;
			    
			    // [JAS]: [TODO] convert to field constants
				$fields = array(
				    'enabled' => $enabled,
					'nickname' => $nickname,
					'protocol' => $protocol,
					'host' => $host,
					'username' => $username,
					'password' => $password,
					'port' => $port
				);
				DAO_Mail::updatePop3Account($id, $fields);
				
			} else {
	            if(!empty($host) && !empty($username)) {
				    // [JAS]: [TODO] convert to field constants
	                $fields = array(
					    'enabled' => 1,
						'nickname' => $nickname,
						'protocol' => $protocol,
						'host' => $host,
						'username' => $username,
						'password' => $password,
						'port' => $port
					);
				    $id = DAO_Mail::createPop3Account($fields);
	            }
			}
		}
		
		DevblocksPlatform::setHttpResponse(new DevblocksHttpResponse(array('config','jobs')));
    }
};
?>