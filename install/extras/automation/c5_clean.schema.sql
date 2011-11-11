-- MySQL dump 10.13  Distrib 5.1.37, for apple-darwin8.11.1 (i386)
--
-- Host: localhost    Database: c5_clean
-- ------------------------------------------------------
-- Server version	5.1.37-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `first_name` varchar(32) NOT NULL DEFAULT '',
  `last_name` varchar(32) NOT NULL DEFAULT '',
  `contact_org_id` int(10) unsigned NOT NULL DEFAULT '0',
  `num_spam` int(10) unsigned NOT NULL DEFAULT '0',
  `num_nonspam` int(10) unsigned NOT NULL DEFAULT '0',
  `is_banned` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_autoreply` int(10) unsigned NOT NULL DEFAULT '0',
  `contact_person_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `contact_org_id` (`contact_org_id`),
  KEY `num_spam` (`num_spam`),
  KEY `num_nonspam` (`num_nonspam`),
  KEY `is_banned` (`is_banned`),
  KEY `last_autoreply` (`last_autoreply`),
  KEY `first_name` (`first_name`(4)),
  KEY `last_name` (`last_name`(4)),
  KEY `contact_person_id` (`contact_person_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `address_outgoing`
--

DROP TABLE IF EXISTS `address_outgoing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_outgoing` (
  `address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `reply_personal` varchar(128) NOT NULL DEFAULT '',
  `reply_signature` text,
  PRIMARY KEY (`address_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `address_to_worker`
--

DROP TABLE IF EXISTS `address_to_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address_to_worker` (
  `address` varchar(128) NOT NULL DEFAULT '',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_confirmed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `code` varchar(32) NOT NULL DEFAULT '',
  `code_expire` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attachment`
--

DROP TABLE IF EXISTS `attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `display_name` varchar(128) NOT NULL DEFAULT '',
  `mime_type` varchar(255) NOT NULL DEFAULT '',
  `storage_size` int(10) unsigned NOT NULL DEFAULT '0',
  `storage_key` varchar(255) NOT NULL DEFAULT '',
  `storage_extension` varchar(255) NOT NULL DEFAULT '',
  `storage_profile_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `storage_profile_id` (`storage_profile_id`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attachment_link`
--

DROP TABLE IF EXISTS `attachment_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachment_link` (
  `guid` varchar(64) NOT NULL DEFAULT '',
  `attachment_id` int(10) unsigned NOT NULL,
  `context` varchar(128) NOT NULL DEFAULT '',
  `context_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`attachment_id`,`context`,`context_id`),
  KEY `guid` (`guid`),
  KEY `attachment_id` (`attachment_id`),
  KEY `context` (`context`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bayes_stats`
--

DROP TABLE IF EXISTS `bayes_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bayes_stats` (
  `spam` int(10) unsigned DEFAULT '0',
  `nonspam` int(10) unsigned DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bayes_words`
--

DROP TABLE IF EXISTS `bayes_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bayes_words` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(64) NOT NULL DEFAULT '',
  `spam` int(10) unsigned DEFAULT '0',
  `nonspam` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `word` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bucket`
--

DROP TABLE IF EXISTS `bucket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bucket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(32) NOT NULL DEFAULT '',
  `is_assignable` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `pos` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `reply_address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reply_personal` varchar(128) NOT NULL DEFAULT '',
  `reply_signature` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_acl`
--

DROP TABLE IF EXISTS `cerb_acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_acl` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_class_loader`
--

DROP TABLE IF EXISTS `cerb_class_loader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_class_loader` (
  `class` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `rel_path` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`class`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_event_point`
--

DROP TABLE IF EXISTS `cerb_event_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_event_point` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `params` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_extension`
--

DROP TABLE IF EXISTS `cerb_extension`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_extension` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `point` varchar(255) NOT NULL DEFAULT '',
  `pos` smallint(5) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `file` varchar(255) NOT NULL DEFAULT '',
  `class` varchar(255) NOT NULL DEFAULT '',
  `params` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_extension_point`
--

DROP TABLE IF EXISTS `cerb_extension_point`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_extension_point` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_patch_history`
--

DROP TABLE IF EXISTS `cerb_patch_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_patch_history` (
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `revision` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `run_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`plugin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_plugin`
--

DROP TABLE IF EXISTS `cerb_plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_plugin` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(64) NOT NULL DEFAULT '',
  `revision` int(10) unsigned NOT NULL DEFAULT '0',
  `dir` varchar(255) NOT NULL DEFAULT '',
  `link` varchar(128) NOT NULL DEFAULT '',
  `manifest_cache_json` mediumtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_property_store`
--

DROP TABLE IF EXISTS `cerb_property_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_property_store` (
  `extension_id` varchar(128) NOT NULL DEFAULT '',
  `property` varchar(128) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`extension_id`,`property`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cerb_uri_routing`
--

DROP TABLE IF EXISTS `cerb_uri_routing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cerb_uri_routing` (
  `uri` varchar(255) NOT NULL DEFAULT '',
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `controller_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`uri`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(128) DEFAULT '',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `comment` mediumtext,
  PRIMARY KEY (`id`),
  KEY `context` (`context`),
  KEY `context_id` (`context_id`),
  KEY `address_id` (`address_id`),
  KEY `created` (`created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community_session`
--

DROP TABLE IF EXISTS `community_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_session` (
  `session_id` varchar(32) NOT NULL DEFAULT '',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  `properties` mediumtext,
  PRIMARY KEY (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community_tool`
--

DROP TABLE IF EXISTS `community_tool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_tool` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `code` varchar(8) NOT NULL DEFAULT '',
  `extension_id` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community_tool_property`
--

DROP TABLE IF EXISTS `community_tool_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_tool_property` (
  `tool_code` varchar(8) NOT NULL DEFAULT '',
  `property_key` varchar(64) NOT NULL DEFAULT '',
  `property_value` text,
  PRIMARY KEY (`tool_code`,`property_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `confirmation_code`
--

DROP TABLE IF EXISTS `confirmation_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `confirmation_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `namespace_key` varchar(255) DEFAULT '',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `confirmation_code` varchar(64) DEFAULT '',
  `meta_json` text,
  PRIMARY KEY (`id`),
  KEY `namespace_key` (`namespace_key`),
  KEY `created` (`created`),
  KEY `confirmation_code` (`confirmation_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact_org`
--

DROP TABLE IF EXISTS `contact_org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_org` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `street` varchar(128) NOT NULL DEFAULT '',
  `city` varchar(64) NOT NULL DEFAULT '',
  `province` varchar(64) NOT NULL DEFAULT '',
  `postal` varchar(20) NOT NULL DEFAULT '',
  `country` varchar(64) NOT NULL DEFAULT '',
  `phone` varchar(32) NOT NULL DEFAULT '',
  `website` varchar(128) NOT NULL DEFAULT '',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact_person`
--

DROP TABLE IF EXISTS `contact_person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_person` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(10) unsigned NOT NULL DEFAULT '0',
  `auth_salt` varchar(64) NOT NULL DEFAULT '',
  `auth_password` varchar(64) NOT NULL DEFAULT '',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `context_activity_log`
--

DROP TABLE IF EXISTS `context_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `context_activity_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activity_point` varchar(128) NOT NULL DEFAULT '',
  `actor_context` varchar(255) NOT NULL DEFAULT '',
  `actor_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `target_context` varchar(255) NOT NULL DEFAULT '',
  `target_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_json` text,
  PRIMARY KEY (`id`),
  KEY `activity_point` (`activity_point`),
  KEY `actor` (`actor_context`,`actor_context_id`),
  KEY `target` (`target_context`,`target_context_id`),
  KEY `created` (`created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `context_link`
--

DROP TABLE IF EXISTS `context_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `context_link` (
  `from_context` varchar(128) DEFAULT '',
  `from_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_context` varchar(128) DEFAULT '',
  `to_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `from_and_to` (`from_context`,`from_context_id`,`to_context`,`to_context_id`),
  KEY `from_context` (`from_context`),
  KEY `from_context_id` (`from_context_id`),
  KEY `to_context` (`to_context`),
  KEY `to_context_id` (`to_context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `context_merge_history`
--

DROP TABLE IF EXISTS `context_merge_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `context_merge_history` (
  `context` varchar(128) NOT NULL DEFAULT '',
  `from_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`context`,`from_context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `context_scheduled_behavior`
--

DROP TABLE IF EXISTS `context_scheduled_behavior`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `context_scheduled_behavior` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `context` varchar(255) NOT NULL DEFAULT '',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `behavior_id` int(10) unsigned NOT NULL DEFAULT '0',
  `run_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `context` (`context`),
  KEY `behavior_id` (`behavior_id`),
  KEY `run_date` (`run_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `crm_opportunity`
--

DROP TABLE IF EXISTS `crm_opportunity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_opportunity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `primary_email_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_date` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_date` int(10) unsigned NOT NULL DEFAULT '0',
  `closed_date` int(10) unsigned NOT NULL DEFAULT '0',
  `is_won` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_closed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `primary_email_id` (`primary_email_id`),
  KEY `updated_date` (`updated_date`),
  KEY `is_closed` (`is_closed`),
  KEY `amount` (`amount`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field`
--

DROP TABLE IF EXISTS `custom_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `type` varchar(1) NOT NULL DEFAULT 'S',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `pos` smallint(5) unsigned NOT NULL DEFAULT '0',
  `options` text,
  `context` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `group_id` (`group_id`),
  KEY `pos` (`pos`),
  KEY `context` (`context`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field_clobvalue`
--

DROP TABLE IF EXISTS `custom_field_clobvalue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field_clobvalue` (
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_value` mediumtext,
  `context` varchar(255) NOT NULL DEFAULT '',
  KEY `field_id` (`field_id`),
  KEY `source_id` (`context_id`),
  KEY `context` (`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field_numbervalue`
--

DROP TABLE IF EXISTS `custom_field_numbervalue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field_numbervalue` (
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_value` int(10) unsigned NOT NULL DEFAULT '0',
  `context` varchar(255) NOT NULL DEFAULT '',
  KEY `field_id` (`field_id`),
  KEY `source_id` (`context_id`),
  KEY `context` (`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `custom_field_stringvalue`
--

DROP TABLE IF EXISTS `custom_field_stringvalue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `custom_field_stringvalue` (
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_value` varchar(255) NOT NULL DEFAULT '',
  `context` varchar(255) NOT NULL DEFAULT '',
  KEY `field_id` (`field_id`),
  KEY `source_id` (`context_id`),
  KEY `context` (`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `decision_node`
--

DROP TABLE IF EXISTS `decision_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decision_node` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `trigger_id` int(10) unsigned NOT NULL DEFAULT '0',
  `node_type` enum('switch','outcome','action') DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `pos` smallint(5) unsigned NOT NULL DEFAULT '0',
  `params_json` longtext,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `trigger_id` (`trigger_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devblocks_session`
--

DROP TABLE IF EXISTS `devblocks_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devblocks_session` (
  `session_key` varchar(64) NOT NULL DEFAULT '',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  `session_data` mediumtext,
  PRIMARY KEY (`session_key`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devblocks_setting`
--

DROP TABLE IF EXISTS `devblocks_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devblocks_setting` (
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `setting` varchar(32) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`plugin_id`,`setting`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devblocks_storage_profile`
--

DROP TABLE IF EXISTS `devblocks_storage_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devblocks_storage_profile` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `extension_id` varchar(255) NOT NULL DEFAULT '',
  `params_json` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `extension_id` (`extension_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devblocks_template`
--

DROP TABLE IF EXISTS `devblocks_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devblocks_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_id` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(255) NOT NULL DEFAULT '',
  `tag` varchar(255) NOT NULL DEFAULT '',
  `last_updated` int(10) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `explorer_set`
--

DROP TABLE IF EXISTS `explorer_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `explorer_set` (
  `hash` varchar(32) NOT NULL DEFAULT '',
  `pos` int(10) unsigned NOT NULL DEFAULT '0',
  `params_json` longtext,
  KEY `hash` (`hash`(4)),
  KEY `pos` (`pos`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feedback_entry`
--

DROP TABLE IF EXISTS `feedback_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_date` int(10) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `quote_text` text,
  `quote_mood` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `quote_address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `source_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `log_date` (`log_date`),
  KEY `worker_id` (`worker_id`),
  KEY `quote_address_id` (`quote_address_id`),
  KEY `quote_mood` (`quote_mood`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fnr_external_resource`
--

DROP TABLE IF EXISTS `fnr_external_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fnr_external_resource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fnr_topic`
--

DROP TABLE IF EXISTS `fnr_topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fnr_topic` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_setting`
--

DROP TABLE IF EXISTS `group_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_setting` (
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `setting` varchar(64) NOT NULL DEFAULT '',
  `value` mediumtext,
  PRIMARY KEY (`group_id`,`setting`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kb_article`
--

DROP TABLE IF EXISTS `kb_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL DEFAULT '',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext,
  `format` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kb_article_to_category`
--

DROP TABLE IF EXISTS `kb_article_to_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_article_to_category` (
  `kb_article_id` int(10) unsigned NOT NULL DEFAULT '0',
  `kb_category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `kb_top_category_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`kb_article_id`,`kb_category_id`),
  KEY `kb_article_id` (`kb_article_id`),
  KEY `kb_category_id` (`kb_category_id`),
  KEY `kb_top_category_id` (`kb_top_category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kb_category`
--

DROP TABLE IF EXISTS `kb_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kb_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mail_queue`
--

DROP TABLE IF EXISTS `mail_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updated` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(255) NOT NULL DEFAULT '',
  `ticket_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hint_to` text,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` longtext,
  `params_json` longtext,
  `is_queued` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `queue_fails` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `queue_delivery_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `worker_id` (`worker_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `updated` (`updated`),
  KEY `is_queued` (`is_queued`),
  KEY `queue_delivery_date` (`queue_delivery_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mail_to_group_rule`
--

DROP TABLE IF EXISTS `mail_to_group_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_to_group_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pos` int(10) unsigned NOT NULL DEFAULT '0',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '',
  `criteria_ser` mediumtext,
  `actions_ser` mediumtext,
  `is_sticky` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sticky_order` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_date` int(10) unsigned DEFAULT NULL,
  `address_id` int(10) unsigned DEFAULT NULL,
  `is_outgoing` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `storage_extension` varchar(255) NOT NULL DEFAULT '',
  `storage_key` varchar(255) NOT NULL DEFAULT '',
  `storage_size` int(10) unsigned NOT NULL DEFAULT '0',
  `storage_profile_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `created_date` (`created_date`),
  KEY `ticket_id` (`ticket_id`),
  KEY `is_outgoing` (`is_outgoing`),
  KEY `worker_id` (`worker_id`),
  KEY `storage_extension` (`storage_extension`),
  KEY `storage_profile_id` (`storage_profile_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message_header`
--

DROP TABLE IF EXISTS `message_header`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_header` (
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `header_name` varchar(64) NOT NULL DEFAULT '',
  `header_value` text,
  KEY `header_name` (`header_name`),
  KEY `header_value` (`header_value`(10)),
  KEY `message_id` (`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_date` int(10) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `context` varchar(255) NOT NULL DEFAULT '',
  `context_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `created_date` (`created_date`),
  KEY `worker_id` (`worker_id`),
  KEY `is_read` (`is_read`),
  KEY `context` (`context`),
  KEY `context_id` (`context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `openid_to_contact_person`
--

DROP TABLE IF EXISTS `openid_to_contact_person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `openid_to_contact_person` (
  `openid_claimed_id` varchar(255) NOT NULL DEFAULT '',
  `contact_person_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hash_key` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`openid_claimed_id`),
  KEY `contact_person_id` (`contact_person_id`),
  KEY `hash_key` (`hash_key`(4))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pop3_account`
--

DROP TABLE IF EXISTS `pop3_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pop3_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `nickname` varchar(128) NOT NULL DEFAULT '',
  `protocol` varchar(32) NOT NULL DEFAULT 'pop3',
  `host` varchar(128) NOT NULL DEFAULT '',
  `username` varchar(128) NOT NULL DEFAULT '',
  `password` varchar(128) NOT NULL DEFAULT '',
  `port` smallint(5) unsigned NOT NULL DEFAULT '110',
  `num_fails` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requester`
--

DROP TABLE IF EXISTS `requester`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requester` (
  `address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ticket_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`address_id`,`ticket_id`),
  KEY `address_id` (`address_id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snippet`
--

DROP TABLE IF EXISTS `snippet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `context` varchar(255) NOT NULL DEFAULT '',
  `content` longtext,
  `owner_context` varchar(128) NOT NULL DEFAULT '',
  `owner_context_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `owner_compound` (`owner_context`,`owner_context_id`),
  KEY `owner_context` (`owner_context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snippet_usage`
--

DROP TABLE IF EXISTS `snippet_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_usage` (
  `snippet_id` int(10) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`snippet_id`,`worker_id`),
  KEY `snippet_id` (`snippet_id`),
  KEY `worker_id` (`worker_id`),
  KEY `hits` (`hits`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `storage_message_content`
--

DROP TABLE IF EXISTS `storage_message_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `storage_message_content` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob,
  `chunk` smallint(5) unsigned DEFAULT '1',
  KEY `chunk` (`chunk`),
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supportcenter_address_share`
--

DROP TABLE IF EXISTS `supportcenter_address_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supportcenter_address_share` (
  `share_address_id` int(10) unsigned NOT NULL,
  `with_address_id` int(10) unsigned NOT NULL,
  `is_enabled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`share_address_id`,`with_address_id`),
  KEY `share_address_id` (`share_address_id`),
  KEY `with_address_id` (`with_address_id`),
  KEY `is_enabled` (`is_enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task`
--

DROP TABLE IF EXISTS `task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `due_date` int(10) unsigned NOT NULL DEFAULT '0',
  `is_completed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `completed_date` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `is_completed` (`is_completed`),
  KEY `completed_date` (`completed_date`),
  KEY `updated_date` (`updated_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket`
--

DROP TABLE IF EXISTS `ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mask` varchar(32) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `is_closed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `bucket_id` int(10) unsigned NOT NULL DEFAULT '0',
  `first_message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created_date` int(10) unsigned DEFAULT NULL,
  `updated_date` int(10) unsigned DEFAULT NULL,
  `due_date` int(10) unsigned DEFAULT NULL,
  `first_wrote_address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_wrote_address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `spam_score` decimal(4,4) NOT NULL DEFAULT '0.0000',
  `spam_training` varchar(1) NOT NULL DEFAULT '',
  `interesting_words` varchar(255) NOT NULL DEFAULT '',
  `is_waiting` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_action_code` varchar(1) NOT NULL DEFAULT 'O',
  `last_message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0',
  `org_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `first_message_id` (`first_message_id`),
  KEY `mask` (`mask`),
  KEY `is_waiting` (`is_waiting`),
  KEY `team_id` (`group_id`),
  KEY `created_date` (`created_date`),
  KEY `updated_date` (`updated_date`),
  KEY `first_wrote_address_id` (`first_wrote_address_id`),
  KEY `last_wrote_address_id` (`last_wrote_address_id`),
  KEY `is_closed` (`is_closed`),
  KEY `category_id` (`bucket_id`),
  KEY `due_date` (`due_date`),
  KEY `is_deleted` (`is_deleted`),
  KEY `last_action_code` (`last_action_code`),
  KEY `spam_score` (`spam_score`),
  KEY `last_message_id` (`last_message_id`),
  KEY `owner_id` (`owner_id`),
  KEY `org_id` (`org_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_mask_forward`
--

DROP TABLE IF EXISTS `ticket_mask_forward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_mask_forward` (
  `old_mask` varchar(32) NOT NULL DEFAULT '',
  `new_mask` varchar(32) NOT NULL DEFAULT '',
  `new_ticket_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`old_mask`),
  KEY `new_ticket_id` (`new_ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timetracking_activity`
--

DROP TABLE IF EXISTS `timetracking_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetracking_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `rate` decimal(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `timetracking_entry`
--

DROP TABLE IF EXISTS `timetracking_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetracking_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `time_actual_mins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `log_date` int(10) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `activity_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_closed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `activity_id` (`activity_id`),
  KEY `worker_id` (`worker_id`),
  KEY `log_date` (`log_date`),
  KEY `is_closed` (`is_closed`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `translation`
--

DROP TABLE IF EXISTS `translation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `string_id` varchar(255) NOT NULL DEFAULT '',
  `lang_code` varchar(16) NOT NULL DEFAULT '',
  `string_default` longtext,
  `string_override` longtext,
  PRIMARY KEY (`id`),
  KEY `string_id` (`string_id`),
  KEY `lang_code` (`lang_code`)
) ENGINE=MyISAM AUTO_INCREMENT=861 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `trigger_event`
--

DROP TABLE IF EXISTS `trigger_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trigger_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `is_disabled` tinyint(4) NOT NULL DEFAULT '0',
  `owner_context` varchar(255) NOT NULL DEFAULT '',
  `owner_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  `event_point` varchar(255) NOT NULL DEFAULT '',
  `pos` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `event_point` (`event_point`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `view_filters_preset`
--

DROP TABLE IF EXISTS `view_filters_preset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `view_filters_preset` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT '',
  `view_class` varchar(255) DEFAULT '',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `params_json` text,
  `sort_json` text,
  PRIMARY KEY (`id`),
  KEY `view_class` (`view_class`),
  KEY `worker_id` (`worker_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `view_rss`
--

DROP TABLE IF EXISTS `view_rss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `view_rss` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` int(10) unsigned NOT NULL DEFAULT '0',
  `params` mediumtext,
  `source_extension` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker`
--

DROP TABLE IF EXISTS `worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(32) DEFAULT '',
  `last_name` varchar(64) DEFAULT '',
  `title` varchar(64) DEFAULT '',
  `email` varchar(128) DEFAULT '',
  `pass` varchar(32) DEFAULT '',
  `is_superuser` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_activity_date` int(10) unsigned DEFAULT NULL,
  `last_activity` mediumtext,
  `is_disabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `last_activity_ip` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `last_activity_date` (`last_activity_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_group`
--

DROP TABLE IF EXISTS `worker_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '',
  `reply_signature` text,
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `reply_address_id` int(10) unsigned NOT NULL DEFAULT '0',
  `reply_personal` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_pref`
--

DROP TABLE IF EXISTS `worker_pref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_pref` (
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `setting` varchar(32) NOT NULL DEFAULT '',
  `value` mediumtext,
  PRIMARY KEY (`worker_id`,`setting`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_role`
--

DROP TABLE IF EXISTS `worker_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `params_json` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_role_acl`
--

DROP TABLE IF EXISTS `worker_role_acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_role_acl` (
  `role_id` int(10) unsigned NOT NULL DEFAULT '0',
  `priv_id` varchar(255) NOT NULL DEFAULT '',
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_to_group`
--

DROP TABLE IF EXISTS `worker_to_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_to_group` (
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_manager` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`worker_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `worker_view_model`
--

DROP TABLE IF EXISTS `worker_view_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_view_model` (
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  `view_id` varchar(255) NOT NULL DEFAULT '',
  `is_ephemeral` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `class_name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `columns_json` text,
  `columns_hidden_json` text,
  `params_editable_json` text,
  `params_default_json` text,
  `params_required_json` text,
  `params_hidden_json` text,
  `render_page` smallint(5) unsigned NOT NULL DEFAULT '0',
  `render_total` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `render_limit` smallint(5) unsigned NOT NULL DEFAULT '0',
  `render_sort_by` varchar(255) NOT NULL DEFAULT '',
  `render_sort_asc` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `render_template` varchar(255) NOT NULL DEFAULT '',
  `render_subtotals` varchar(255) NOT NULL DEFAULT '',
  `render_filters` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `worker_to_view_id` (`worker_id`,`view_id`),
  KEY `worker_id` (`worker_id`),
  KEY `view_id` (`view_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `workspace`
--

DROP TABLE IF EXISTS `workspace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workspace` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `owner_context` varchar(255) NOT NULL DEFAULT '',
  `owner_context_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `owner_context` (`owner_context`),
  KEY `owner_context_id` (`owner_context_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `workspace_list`
--

DROP TABLE IF EXISTS `workspace_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workspace_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `list_view` text,
  `list_pos` smallint(5) unsigned DEFAULT '0',
  `workspace_id` int(10) unsigned NOT NULL DEFAULT '0',
  `context` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `workspace_id` (`workspace_id`),
  KEY `context` (`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `workspace_to_endpoint`
--

DROP TABLE IF EXISTS `workspace_to_endpoint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workspace_to_endpoint` (
  `workspace_id` int(10) unsigned NOT NULL DEFAULT '0',
  `endpoint` varchar(128) NOT NULL DEFAULT '',
  `pos` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `worker_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`workspace_id`,`worker_id`,`endpoint`),
  KEY `workspace_id` (`workspace_id`),
  KEY `endpoint` (`endpoint`),
  KEY `worker_id` (`worker_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-11-11 11:26:49
