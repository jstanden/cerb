SET @support_email = 'support@example.com';
SET @worker_email = 'admin@example.com';
SET @worker_salt = 'ABCDEFGHIJKL';
SET @worker_pass = 'secret1234';

--
-- Dumping data for table `address`
--

INSERT INTO `address` VALUES (1,@support_email,'','',0,0,0,0,0,0,UNIX_TIMESTAMP());
INSERT INTO `address` VALUES (2,@worker_email,'','',0,0,0,0,0,0,UNIX_TIMESTAMP());

--
-- Dumping data for table `address_outgoing`
--

INSERT INTO `address_outgoing` VALUES (1,1,'','',0);

--
-- Dumping data for table `address_to_worker`
--

INSERT INTO `address_to_worker` VALUES (@worker_email,1,1,'',0);


--
-- Dumping data for table `cerb_property_store`
--

INSERT INTO `cerb_property_store` VALUES ('cron.heartbeat','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.heartbeat','duration','5');
INSERT INTO `cerb_property_store` VALUES ('cron.heartbeat','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.heartbeat','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.import','enabled','');
INSERT INTO `cerb_property_store` VALUES ('cron.import','duration','0');
INSERT INTO `cerb_property_store` VALUES ('cron.import','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.import','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.storage','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.storage','duration','1');
INSERT INTO `cerb_property_store` VALUES ('cron.storage','term','h');
INSERT INTO `cerb_property_store` VALUES ('cron.storage','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.search','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.search','duration','10');
INSERT INTO `cerb_property_store` VALUES ('cron.search','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.search','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.mail_queue','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.mail_queue','duration','1');
INSERT INTO `cerb_property_store` VALUES ('cron.mail_queue','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.mail_queue','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.virtual_attendant.scheduled_behavior','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.virtual_attendant.scheduled_behavior','duration','1');
INSERT INTO `cerb_property_store` VALUES ('cron.virtual_attendant.scheduled_behavior','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.virtual_attendant.scheduled_behavior','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.pop3','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.pop3','duration','5');
INSERT INTO `cerb_property_store` VALUES ('cron.pop3','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.pop3','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.parser','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.parser','duration','1');
INSERT INTO `cerb_property_store` VALUES ('cron.parser','term','m');
INSERT INTO `cerb_property_store` VALUES ('cron.parser','lastrun',UNIX_TIMESTAMP()-3600);
INSERT INTO `cerb_property_store` VALUES ('cron.maint','enabled','1');
INSERT INTO `cerb_property_store` VALUES ('cron.maint','duration','24');
INSERT INTO `cerb_property_store` VALUES ('cron.maint','term','h');
INSERT INTO `cerb_property_store` VALUES ('cron.maint','lastrun',UNIX_TIMESTAMP()-3600);

--
-- Dumping data for table `contact_org`
--

INSERT INTO `contact_org` VALUES (1, 'Webgroup Media, LLC.', 'PO BOX 1206', 'Brea', 'California', '92822', 'USA', '+1 714-671-9090', 'http://www.cerbweb.com/', UNIX_TIMESTAMP());

--
-- Dumping data for table `devblocks_setting`
--

INSERT INTO `devblocks_setting` VALUES ('cerberusweb.core','helpdesk_title','Cerb - a fast and flexible web-based platform for business collaboration and automation.');
INSERT INTO `devblocks_setting` VALUES ('cerberusweb.core','smtp_host','localhost');
INSERT INTO `devblocks_setting` VALUES ('cerberusweb.core','smtp_port','25');
INSERT INTO `devblocks_setting` VALUES ('cerberusweb.core','smtp_auth_enabled','0');
INSERT INTO `devblocks_setting` VALUES ('cerberusweb.core','smtp_enc','None');

--
-- Dumping data for table `worker`
--

INSERT INTO `worker` VALUES (1,'Super','User','Administrator',@worker_email,1,NULL,NULL,0,0,'login.password');

--
-- Dumping data for table `worker_auth_hash`
--

INSERT INTO `worker_auth_hash` VALUES (1,SHA1(CONCAT(@worker_salt,MD5(@worker_pass))),@worker_salt,0);

--
-- Dumping data for table `worker_group`
--

INSERT INTO `worker_group` VALUES (1,'Dispatch',NULL,1,0,'',0);
INSERT INTO `worker_group` VALUES (2,'Support',NULL,0,0,'',0);
INSERT INTO `worker_group` VALUES (3,'Sales',NULL,0,0,'',0);

--
-- Dumping data for table `worker_role`
--

INSERT INTO `worker_role` VALUES (1,'Default','{\"who\":\"all\",\"what\":\"all\"}');

--
-- Dumping data for table `worker_to_group`
--

INSERT INTO `worker_to_group` VALUES (1,1,1);
INSERT INTO `worker_to_group` VALUES (1,2,1);
INSERT INTO `worker_to_group` VALUES (1,3,1);

--
-- Dumping data for table `virtual_attendant`
--

INSERT INTO `virtual_attendant` VALUES (1,'Cerb\'s Virtual Attendant','cerberusweb.contexts.app',0,0,'{\"events\":{\"mode\":\"all\",\"items\":[]},\"actions\":{\"mode\":\"all\",\"items\":[]}}',UNIX_TIMESTAMP(),UNIX_TIMESTAMP());


--
-- Dumping data for table `trigger_event`
--

INSERT INTO `trigger_event` VALUES (1,'Delivery Blacklist',0,0,'event.mail.received.app',1,0,NULL,NULL);

--
-- Dumping data for table `decision_node`
--

INSERT INTO `decision_node` VALUES (1,0,1,'switch','First match:',0,'');

