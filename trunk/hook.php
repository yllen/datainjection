<?php

/*
 * @version $Id: HEADER 14684 2011-06-11 06:32:40Z remi $
 LICENSE

 This file is part of the order plugin.

 Datainjection plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Datainjection plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with datainjection; along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   datainjection
 @author    the datainjection plugin team
 @copyright Copyright (c) 2010-2011 Order plugin team
 @license   GPLv2+
            http://www.gnu.org/licenses/gpl.txt
 @link      https://forge.indepnet.net/projects/datainjection
 @link      http://www.glpi-project.org/
 @since     2009
 ---------------------------------------------------------------------- */

function plugin_datainjection_registerMethods() {
   global $WEBSERVICES_METHOD;

   $methods = array('getModel'      =>'methodGetModel',
                    'listModels'    =>'methodListModels',
                    'inject'        =>'methodInject',
                    'listItemtypes' =>'methodListItemtypes');

   foreach ($methods as $code => $method) {
      $WEBSERVICES_METHOD['datainjection.'.$code]  = array('PluginDatainjectionWebservice',
                                                            $method);
   }
}


function plugin_pre_item_delete_datainjection($input) {

   if (isset ($input["_item_type_"])) {
      switch ($input["_item_type_"]) {
         case 'Profile' :
            // Manipulate data if needed
            $PluginDatainjectionProfile = new PluginDatainjectionProfile;
            $PluginDatainjectionProfile->cleanProfiles($input["ID"]);
            break;
      }
   }
   return $input;
}


function plugin_datainjection_changeprofile() {

   $plugin = new Plugin;

   if ($plugin->isInstalled("datainjection") && $plugin->isActivated("datainjection")) {
      $prof = new PluginDatainjectionProfile();

      if ($prof->getFromDB($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION["glpi_plugin_datainjection_profile"] = $prof->fields;
      } else {
         unset ($_SESSION["glpi_plugin_datainjection_profile"]);
      }
   }
}


function plugin_headings_actions_datainjection($item) {

   switch (get_class($item)) {
      case 'Profile' :
         return array(1 => "plugin_headings_datainjection");
   }
   return false;
}


function plugin_get_headings_datainjection($item, $withtemplate) {
   global $LANG;

   switch (get_class($item)) {
      case 'Profile' :
         if ($item->fields['interface']=='central') {
            return array(1 => $LANG['datainjection']['name'][1]);
         }
         return array();
   }
   return false;
}


function plugin_headings_datainjection($item,$withtemplate=0) {
   global $CFG_GLPI;

   switch (get_class($item)) {
      case 'Profile' :
         $profile = new PluginDatainjectionProfile;
         if (!$profile->getFromDB($item->fields['id'])) {
            plugin_datainjection_createaccess($item->fields['id']);
         }
         $profile->showForm($item->fields['id']);
         break;

      default :
         break;
   }
}


function plugin_datainjection_install() {
   global $DB;

   switch (plugin_datainjection_needUpdateOrInstall()) {
      case -1 :
         return true;

      case 0 :
         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_datainjection_models` (
                     `id` int(11) NOT NULL auto_increment,
                     `name` varchar(255) NOT NULL,
                     `comment` text NULL,
                     `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
                     `filetype` varchar(255) NOT NULL default 'csv',
                     `itemtype` varchar(255) NOT NULL default '',
                     `entities_id` int(11) NOT NULL default '-1',
                     `behavior_add` tinyint(1) NOT NULL default '1',
                     `behavior_update` tinyint(1) NOT NULL default '0',
                     `can_add_dropdown` tinyint(1) NOT NULL default '0',
                     `can_overwrite_if_not_empty` int(1) NOT NULL default '1',
                     `is_private` tinyint(1) NOT NULL default '1',
                     `is_recursive` tinyint(1) NOT NULL default '0',
                     `perform_network_connection` tinyint(1) NOT NULL default '0',
                     `users_id` int(11) NOT NULL,
                     `date_format` varchar(11) NOT NULL default 'yyyy-mm-dd',
                     `float_format` tinyint( 1 ) NOT NULL DEFAULT '0',
                     `port_unicity` tinyint( 1 ) NOT NULL DEFAULT '0',
                     `step` int( 11 ) NOT NULL DEFAULT '0',
                     PRIMARY KEY  (`id`)
                   ) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());

         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_datainjection_modelcsvs` (
                     `id` int(11) NOT NULL auto_increment,
                     `models_id` int(11) NOT NULL,
                     `itemtype` varchar(255) NOT NULL default '',
                     `delimiter` varchar(1) NOT NULL default ';',
                     `is_header_present` tinyint(1) NOT NULL default '1',
                     PRIMARY KEY  (`ID`)
                   ) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());

         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_datainjection_mappings` (
                     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                     `models_id` INT( 11 ) NOT NULL ,
                     `itemtype` varchar(255) NOT NULL default '',
                     `rank` INT( 11 ) NOT NULL ,
                     `name` VARCHAR( 255 ) NOT NULL ,
                     `value` VARCHAR( 255 ) NOT NULL ,
                     `is_mandatory` TINYINT( 1 ) NOT NULL DEFAULT '0'
                   ) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
         $DB->query($query) or die($DB->error());

         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_datainjection_infos` (
                     `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                     `models_id` INT( 11 ) NOT NULL ,
                     `itemtype` varchar(255) NOT NULL default '',
                     `value` VARCHAR( 255 ) NOT NULL ,
                     `is_mandatory` TINYINT( 1 ) NOT NULL DEFAULT '0'
                   ) ENGINE = MYISAM CHARSET=utf8 COLLATE=utf8_unicode_ci ;";
         $DB->query($query) or die($DB->error());

         $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_datainjection_profiles` (
                     `id` int(11) NOT NULL auto_increment,
                     `name` varchar(255) default NULL,
                     `is_default` TINYINT(1) NOT NULL default '0',
                     `model` char(1) default NULL,
                     PRIMARY KEY  (`ID`)
                   ) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die($DB->error());

         if (!is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
            @ mkdir(PLUGIN_DATAINJECTION_UPLOAD_DIR)
               or die("Can't create folder " . PLUGIN_DATAINJECTION_UPLOAD_DIR);

            plugin_datainjection_createfirstaccess($_SESSION["glpiactiveprofile"]["id"]);
         }
         break;

      default :
         break;

      case 1 :
         //When updating, check if the upload folder is already present
         if (!is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
            @ mkdir(PLUGIN_DATAINJECTION_UPLOAD_DIR)
               or die("Can't create folder " . PLUGIN_DATAINJECTION_UPLOAD_DIR);
         }

         //Old temporary directory, needs to be removed !
         if (is_dir(GLPI_PLUGIN_DOC_DIR."/data_injection/")) {
            deleteDir(GLPI_PLUGIN_DOC_DIR."/data_injection/");
         }

         if (TableExists("glpi_plugin_data_injection_models")
             && !FieldExists("glpi_plugin_data_injection_models","recursive")) {
            // Update
            plugin_datainjection_update131_14();
         }

         if (TableExists("glpi_plugin_data_injection_models")
             && !FieldExists("glpi_plugin_data_injection_models","port_unicity")) {
            plugin_datainjection_update14_15();
         }

         if (!TableExists("glpi_plugin_datainjection_models")) {
            plugin_datainjection_update15_170();
         }

         if (!TableExists("glpi_plugin_datainjection_modelcsvs")) {
            plugin_datainjection_update170_20();
         }
         break;
   }

   plugin_datainjection_changeprofile();
   return true;
}


function plugin_datainjection_createfirstaccess($ID) {
   global $DB;

   include_once(GLPI_ROOT."/plugins/datainjection/inc/profile.class.php");
   $PluginDatainjectionProfile = new PluginDatainjectionProfile();

   if (!$PluginDatainjectionProfile->getFromDB($ID)) {
      $Profile = new Profile();
      $Profile->getFromDB($ID);
      $name    = $Profile->fields["name"];

      $query = "INSERT INTO `glpi_plugin_datainjection_profiles`
                       (`id`, `name` , `is_default`, `model`)
                VALUES ('$ID', '$name', '0', 'w');";
      $DB->query($query);
   }
}


function plugin_datainjection_uninstall() {
   global $DB;

   $tables = array("glpi_plugin_datainjection_models",
                   "glpi_plugin_datainjection_modelcsvs",
                   "glpi_plugin_datainjection_mappings",
                   "glpi_plugin_datainjection_infos",
                   "glpi_plugin_datainjection_filetype",
                   "glpi_plugin_datainjection_profiles");

   foreach ($tables as $table) {
      if (TableExists($table)) {
         $DB->query("DROP TABLE IF EXISTS `$table`") or die($DB->error());
      }
   }

   if (is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
      deleteDir(PLUGIN_DATAINJECTION_UPLOAD_DIR);
   }

   plugin_init_datainjection();
   return true;
}


function plugin_datainjection_update131_14() {
   global $DB;

   $sql = "ALTER TABLE `glpi_plugin_data_injection_models`
           ADD `float_format` INT( 1 ) NOT NULL DEFAULT '0'";
   $DB->query($sql);

   //Template recursivity : need standardize names in order to use privatePublicSwitch
   $sql = "ALTER TABLE `glpi_plugin_data_injection_models`
           CHANGE `user_id` `FK_users` INT( 11 ) NOT NULL";
   $DB->query($sql);

   $sql = "ALTER TABLE `glpi_plugin_data_injection_models`
           CHANGE `public` `private` INT( 1 ) NOT NULL  DEFAULT '0'";
   $DB->query($sql);

   $sql = "UPDATE `glpi_plugin_data_injection_models`
           SET `FK_entities` = '-1', `private` = '1'
           WHERE `private` = '0'";
   $DB->query($sql);

   $sql = "UPDATE `glpi_plugin_data_injection_models`
           SET `private` = '0'
           WHERE `private` = '1'
                AND `FK_entities` > '0'";
   $DB->query($sql);

   $sql = "ALTER TABLE `glpi_plugin_data_injection_models`
           ADD `recursive` INT( 1 ) NOT NULL DEFAULT '0'";
   $DB->query($sql);

   $sql = "UPDATE `glpi_plugin_data_injection_profiles`
           SET `create_model` = `use_model`
           WHERE `create_model` IS NULL";
   $DB->query($sql);

   $sql = "ALTER TABLE `glpi_plugin_data_injection_profiles`
           DROP `use_model`";
   $DB->query($sql);

   $sql = " ALTER TABLE `glpi_plugin_data_injection_profiles`
            CHANGE `create_model` `model` CHAR( 1 )
               CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL";
   $DB->query($sql);
}


function plugin_datainjection_update14_15() {
   global $DB;

   $query = "ALTER TABLE `glpi_plugin_data_injection_models`
             ADD `port_unicity` INT( 1 ) NOT NULL DEFAULT '0'";
   $DB->query($query);
}


function plugin_datainjection_update15_170() {
   global $DB;

   $tables = array("glpi_plugin_data_injection_models"     => "glpi_plugin_datainjection_models",
                   "glpi_plugin_data_injection_models_csv" => "glpi_plugin_datainjection_models_csv",
                   "glpi_plugin_data_injection_models_csv" => "glpi_plugin_datainjection_models_csv",
                   "glpi_plugin_data_injection_mappings"   => "glpi_plugin_datainjection_mappings",
                   "glpi_plugin_data_injection_infos"      => "glpi_plugin_datainjection_infos",
                   "glpi_plugin_data_injection_filetype"   => "glpi_plugin_datainjection_filetype",
                   "glpi_plugin_data_injection_profiles"   => "glpi_plugin_datainjection_profiles");

   foreach ($tables as $oldname => $newname) {
      $query = "RENAME TABLE IF EXISTS `$oldname` TO `$newname`";
      $DB->query($query);
   }
}


function plugin_datainjection_update170_20() {
   global $DB;

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_models`
                 CHANGE `type` `filetype` VARCHAR( 255 ) NOT NULL DEFAULT 'csv'";

   $queries[] = "DROP TABLE `glpi_plugin_datainjection_filetype`";

   $queries[] = "RENAME TABLE `glpi_plugin_datainjection_models_csv`
                 TO `glpi_plugin_datainjection_modelcsvs`";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_models`
                 ADD `step` TINYINT( 1 ) NOT NULL DEFAULT '0'";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_mappings`
                 CHANGE `mandatory` `is_mandatory` TINYINT( 1 ) NOT NULL DEFAULT '0'";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_infos`
                 CHANGE `mandatory` `is_mandatory` TINYINT( 1 ) NOT NULL DEFAULT '0'";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_mappings`
                 CHANGE  `type`  `itemtype` VARCHAR( 255 ) NOT NULL DEFAULT ''";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_infos`
                 CHANGE `type` `itemtype` VARCHAR( 255 ) NOT NULL DEFAULT ''";

   $queries[] = "ALTER TABLE  `glpi_plugin_datainjection_mappings`
                 CHANGE `model_id` `models_id` INT( 11 ) NOT NULL";

   $queries[] = "ALTER TABLE `glpi_plugin_datainjection_infos`
                 CHANGE `model_id` `models_id` INT( 11 ) NOT NULL";

   $queries[] = "ALTER TABLE  `glpi_plugin_datainjection_models`
                 CHANGE `comments` `comment` TEXT
                     CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL ,
                 CHANGE `device_type` `itemtype` VARCHAR( 255 ) NOT NULL DEFAULT '',
                 CHANGE `FK_entities` `entities_id` INT( 11 ) NOT NULL,
                 CHANGE `private` `is_private` TINYINT( 1 ) NOT NULL DEFAULT '0',
                 CHANGE `FK_users` `users_id` INT( 11 ) NOT NULL,
                 CHANGE `recursive` `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0'";

   $queries[] = "UPDATE `glpi_plugin_datainjection_models`
                 SET `step` = '5'";

   $queries[] = "ALTER TABLE  `glpi_plugin_datainjection_modelcsvs`
                 CHANGE `model_id` `models_id` INT( 11 ) NOT NULL ,
                 CHANGE `device_type` `itemtype` VARCHAR( 255 ) NOT NULL DEFAULT '',
                 CHANGE `header_present` `is_header_present` TINYINT( 1 ) NOT NULL DEFAULT '1'";

   $queries[] = "UPDATE `glpi_plugin_datainjection_models`
                 SET `filetype` = 'csv'";

   foreach ($queries as $query) {
      $DB->query($query);
   }

   $glpitables = array('glpi_plugin_datainjection_models',
                       'glpi_plugin_datainjection_mappings',
                       'glpi_plugin_datainjection_infos',
                       'glpi_plugin_datainjection_modelcsvs',
                       'glpi_plugin_datainjection_profiles');

   foreach ($glpitables as $table) {
      $query = "ALTER TABLE `$table`
                CHANGE `ID` `id` INT( 11 ) NOT NULL AUTO_INCREMENT";
      $DB->query($query) or die ("Datainjection : Rename ID to id");
   }

   $glpitables = array('glpi_plugin_datainjection_models',
                       'glpi_plugin_datainjection_mappings',
                       'glpi_plugin_datainjection_infos',
                       'glpi_plugin_datainjection_modelcsvs');
   Plugin::migrateItemType (array(), array(), $glpitables);

   $query = "UPDATE `glpi_plugin_datainjection_mappings`
             SET `itemtype` = 'none' , `value`='none'
             WHERE `itemtype` = '-1'";
   $DB->query($query) or die ("Datainjection mappings tables : error updating not mapped fields");

   $query = "UPDATE `glpi_plugin_datainjection_infos`
             SET `itemtype` = 'none', `value` = 'none'
             WHERE `itemtype` = '-1'";
   $DB->query($query) or die ("Datainjection infos table : error updating not mapped fields");

   $foreignkeys = array('assign'
                           => array(array('to'     => 'users_id_assign',
                                          'tables' => array('glpi_tickets'))),

                        'assign_group'
                           => array(array('to'     => 'groups_id_assign',
                                          'tables' => array('glpi_tickets'))),

                        'assign_ent'
                           => array(array('to'     => 'suppliers_id_assign',
                                          'tables' => array('glpi_tickets'))),

                        'auth_method'
                           => array(array('to'      => 'authtype',
                                          'noindex' => array('glpi_users'),
                                          'tables'  => array('glpi_users'))),

                        'author'
                           => array(array('to'     => 'users_id',
                                          'tables' => array('glpi_ticketfollowups', 'glpi_knowbaseitems',
                                                            'glpi_tickets'))),

                        'auto_update'
                           => array(array('to'     => 'autoupdatesystems_id',
                                          'tables' => array('glpi_computers'))),

                        'budget'
                           => array(array('to'     => 'budgets_id',
                                          'tables' => array('glpi_infocoms'))),

                        'buy_version'
                           => array(array('to'     => 'softwareversions_id_buy',
                                          'tables' => array('glpi_softwarelicenses'))),

                        'category'
                           => array(array('to'     => 'ticketcategories_id',
                                          'tables' => array('glpi_tickets')),
                                    array('to'     => 'softwarecategories_id',
                                          'tables' => array('glpi_softwares'))),

                        'categoryID'
                           => array(array('to'     => 'knowbaseitemcategories_id',
                                          'tables' => array('glpi_knowbaseitems'))),

                        'cID'
                           => array(array('to'     => 'computers_id',
                                          'tables' => array('glpi_computers_softwareversions'))),

                        'computer'
                           => array(array('to'     => 'items_id',
                                          'tables' => array('glpi_tickets'))),

                        'computer_id'
                           => array(array('to'     => 'computers_id',
                                          'tables' => array('glpi_registrykeys'))),

                        'contract_type'
                           => array(array('to'     => 'contracttypes_id',
                                          'tables' => array('glpi_contracts'))),

                        'default_rubdoc_tracking'
                           => array(array('to'       => 'documentcategories_id_forticket',
                                          'tables'   => array('glpi_configs'),
                                          'comments' => array('glpi_configs' => 'default category for documents added with a ticket'))),

                        'device_type'
                           => array(array('to'     => 'itemtype',
                                          'tables' => array('glpi_alerts', 'glpi_contracts_items',
                                                            'glpi_documents_items', 'glpi_infocoms',
                                                            'glpi_bookmarks', 'glpi_bookmarks_users',
                                                            'glpi_links_itemtypes', 'glpi_networkports',
                                                            'glpi_reservationitems', 'glpi_tickets'))),

                        'domain'
                           => array(array('to'     => 'domains_id',
                                          'tables' => array('glpi_computers', 'glpi_networkequipments',
                                                            'glpi_printers'))),

                        'end1'
                           => array(array('to'       => 'items_id',
                                          'tables'   => array('glpi_computers_items'),
                                          'comments' => array('glpi_computers_items' => 'RELATION to various table, according to itemtype (ID)')),
                                    array('to'     => 'networkports_id_1',
                                          'tables' => array('glpi_networkports_networkports'))),

                        'end2'
                           => array(array('to'     => 'computers_id',
                                          'tables' => array('glpi_computers_items')),
                                    array('to'     => 'networkports_id_2',
                                          'tables' => array('glpi_networkports_networkports')) ),

                        'firmware'
                           => array(array('to'     => 'networkequipmentfirmwares_id',
                                          'tables' => array('glpi_networkequipments'))),

                        'FK_bookmark'
                           => array(array('to'     => 'bookmarks_id',
                                          'tables' => array('glpi_bookmarks_users'))),

                        'FK_computers'
                           => array(array('to'     => 'computers_id',
                                          'tables' => array('glpi_computerdisks',
                                                            'glpi_softwarelicenses'))),

                        'FK_contact'
                           => array(array('to'     => 'contacts_id',
                                          'tables' => array('glpi_contacts_suppliers'))),

                        'FK_contract'
                           => array(array('to'     => 'contracts_id',
                                          'tables' => array('glpi_contracts_suppliers',
                                                            'glpi_contracts_items'))),

                        'FK_device'
                           => array(array('to'    => 'items_id',
                                         'tables' => array('glpi_alerts', 'glpi_contracts_items',
                                                           'glpi_documents_items', 'glpi_infocoms'))),

                        'FK_doc'
                           => array(array('to'     => 'documents_id',
                                          'tables' => array('glpi_documents_items'))),

                        'manufacturer'
                           => array(array('to'     => 'suppliers_id',
                                          'tables' => array('glpi_contacts_suppliers',
                                                            'glpi_contracts_suppliers', 'glpi_infocoms')),
                                    array('to'     => 'manufacturers_id',
                                          'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                            'glpi_consumableitems', 'glpi_devicecases',
                                                            'glpi_devicecontrols', 'glpi_devicedrives',
                                                            'glpi_devicegraphiccards', 'glpi_deviceharddrives',
                                                            'glpi_devicenetworkcards', 'glpi_devicemotherboards',
                                                            'glpi_devicepcis', 'glpi_devicepowersupplies',
                                                            'glpi_deviceprocessors', 'glpi_devicememories',
                                                            'glpi_devicesoundcards', 'glpi_monitors',
                                                            'glpi_networkequipments', 'glpi_peripherals',
                                                            'glpi_phones', 'glpi_printers',
                                                            'glpi_softwares'))),

                        'FK_entities'
                           => array(array('to'      => 'entities_id',
                                          'tables'  => array('glpi_bookmarks', 'glpi_cartridgeitems',
                                                             'glpi_computers', 'glpi_consumableitems',
                                                             'glpi_contacts', 'glpi_contracts',
                                                             'glpi_documents', 'glpi_locations',
                                                             'glpi_netpoints', 'glpi_suppliers',
                                                             'glpi_entitydatas', 'glpi_groups',
                                                             'glpi_knowbaseitems', 'glpi_links',
                                                             'glpi_mailcollectors', 'glpi_monitors',
                                                             'glpi_networkequipments', 'glpi_peripherals',
                                                             'glpi_phones', 'glpi_printers',
                                                             'glpi_reminders', 'glpi_rules',
                                                             'glpi_softwares', 'glpi_softwarelicenses',
                                                             'glpi_tickets', 'glpi_users',
                                                             'glpi_profiles_users'),
                                          'default' => array('glpi_bookmarks' => "-1"))),

                        'FK_filesystems'
                           => array(array('to'     => 'filesystems_id',
                                          'tables' => array('glpi_computerdisks'))),

                        'FK_glpi_cartridges_type'
                           => array(array('to'     => 'cartridgeitems_id',
                                          'tables' => array('glpi_cartridges',
                                                            'glpi_cartridges_printermodels'))),

                        'FK_glpi_consumables_type'
                           => array(array('to'     => 'consumableitems_id',
                                          'tables' => array('glpi_consumables'))),

                        'FK_glpi_dropdown_model_printers'
                           => array(array('to'     => 'printermodels_id',
                                          'tables' => array('glpi_cartridges_printermodels'))),

                        'FK_glpi_printers'
                           => array(array('to'     => 'printers_id',
                                          'tables' => array('glpi_cartridges'))),

                        'FK_group'
                           => array(array('to'     => 'groups_id',
                                          'tables' => array('glpi_tickets'))),

                        'FK_groups'
                           => array(array('to'     => 'groups_id',
                                          'tables' => array('glpi_computers', 'glpi_monitors',
                                                            'glpi_networkequipments','glpi_peripherals',
                                                            'glpi_phones', 'glpi_printers',
                                                            'glpi_softwares', 'glpi_groups_users'))),

                        'FK_interface'
                           => array(array('to'     => 'interfacetypes_id',
                                          'tables' => array('glpi_devicegraphiccards'))),

                        'FK_item'
                           => array(array('to'     => 'items_id',
                                          'tables' => array('glpi_mailingsettings'))),

                        'FK_links'
                           => array(array('to'     => 'links_id',
                                          'tables' => array('glpi_links_itemtypes'))),

                        'FK_port'
                           => array(array('to'     => 'networkports_id',
                                          'tables' => array('glpi_networkports_vlans'))),

                        'FK_profiles'
                           => array(array('to'     => 'profiles_id',
                                          'tables' => array('glpi_profiles_users', 'glpi_users'))),

                        'FK_users'
                           => array(array('to'     => 'users_id',
                                          'tables' => array('glpi_bookmarks', 'glpi_displaypreferences',
                                                            'glpi_documents', 'glpi_groups',
                                                            'glpi_reminders', 'glpi_bookmarks_users',
                                                            'glpi_groups_users', 'glpi_profiles_users',
                                                            'glpi_computers', 'glpi_monitors',
                                                            'glpi_networkequipments', 'glpi_peripherals',
                                                            'glpi_phones', 'glpi_printers',
                                                            'glpi_softwares'))),

                        'FK_vlan'
                           => array(array('to'     => 'vlans_id',
                                          'tables' => array('glpi_networkports_vlans'))),

                        'glpi_id'
                           => array(array('to'     => 'computers_id',
                                          'tables' => array('glpi_ocslinks'))),

                        'id_assign'
                           => array(array('to'     => 'users_id',
                                          'tables' => array('glpi_ticketplannings'))),

                        'id_auth'
                           => array(array('to'     => 'auths_id',
                                          'tables' => array('glpi_users'))),

                        'id_device'
                           => array(array('to'     => 'items_id',
                                          'tables' => array('glpi_reservationitems'))),

                        'id_item'
                           => array(array('to'     => 'reservationitems_id',
                                          'tables' => array('glpi_reservations'))),

                        'id_user'
                           => array(array('to'     => 'users_id',
                                          'tables' => array('glpi_consumables',
                                                            'glpi_reservations'))),

                        'iface'
                           => array(array('to'     => 'networkinterfaces_id',
                                          'tables' => array('glpi_networkports'))),

                        'interface'
                           => array(array('to'     => 'interfacetypes_id',
                                          'tables' => array('glpi_devicecontrols', 'glpi_deviceharddrives',
                                                            'glpi_devicedrives'))),

                        'location'
                           => array(array('to'     => 'locations_id',
                                          'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                            'glpi_consumableitems', 'glpi_netpoints',
                                                            'glpi_monitors', 'glpi_networkequipments',
                                                            'glpi_peripherals', 'glpi_phones', 'glpi_printers',
                                                            'glpi_users', 'glpi_softwares'))),

                        'model'
                           => array(array('to'     => 'computermodels_id',
                                          'tables' => array('glpi_computers')),
                                    array('to'     => 'monitormodels_id',
                                          'tables' => array('glpi_monitors')),
                                    array('to'     => 'networkequipmentmodels_id',
                                          'tables' => array('glpi_networkequipments')),
                                    array('to'     => 'peripheralmodels_id',
                                          'tables' => array('glpi_peripherals')),
                                    array('to'     => 'phonemodels_id',
                                          'tables' => array('glpi_phones')),
                                    array('to'     => 'printermodels_id',
                                          'tables' => array('glpi_printers'))),

                        'netpoint'
                           => array(array('to'     => 'netpoints_id',
                                          'tables' => array('glpi_networkports'))),

                        'network'
                           => array(array('to'     => 'networks_id',
                                          'tables' => array('glpi_computers', 'glpi_networkequipments',
                                                            'glpi_printers'))),

                        'on_device'
                           => array(array('to'     => 'items_id',
                                          'tables' => array('glpi_networkports'))),

                        'os'
                           => array(array('to'     => 'operatingsystems_id',
                                          'tables' => array('glpi_computers'))),

                        'os_license_id'
                           => array(array('to'     => 'os_licenseid',
                                          'tables' => array('glpi_computers'))),

                        'os_version'
                           => array(array('to'     => 'operatingsystemversions_id',
                                          'tables' => array('glpi_computers'))),

                        'parentID'
                           => array(array('to' => 'knowbaseitemcategories_id',
                                          'tables' => array('glpi_knowbaseitemcategories')),
                                    array('to'     => 'locations_id',
                                          'tables' => array('glpi_locations')),
                                    array('to'     => 'ticketcategories_id',
                                          'tables' => array('glpi_ticketcategories')),
                                    array('to'     => 'entities_id',
                                          'tables' => array('glpi_entities'))    ),

                        'platform'
                           => array(array('to'     => 'operatingsystems_id',
                                          'tables' => array('glpi_softwares'))),

                        'power'
                           => array(array('to'     => 'phonepowersupplies_id',
                                          'tables' => array('glpi_phones'))),

                        'recipient'
                           => array(array('to'     => 'users_id_recipient',
                                          'tables' => array('glpi_tickets'))),

                        'rubrique'
                           => array(array('to'     => 'documentcategories_id',
                                          'tables' => array('glpi_documents'))),

                         'sID'
                           => array(array('to'     => 'softwares_id',
                                          'tables' => array('glpi_softwarelicenses',
                                                            'glpi_softwareversions'))),

                        'state'
                           => array(array('to'     => 'states_id',
                                          'tables' => array('glpi_computers', 'glpi_monitors',
                                                            'glpi_networkequipments', 'glpi_peripherals',
                                                            'glpi_phones', 'glpi_printers',
                                                            'glpi_softwareversions'))),

                        'tech_num'
                           => array(array('to'     => 'users_id_tech',
                                          'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                            'glpi_consumableitems', 'glpi_monitors',
                                                            'glpi_networkequipments', 'glpi_peripherals',
                                                            'glpi_phones', 'glpi_printers',
                                                            'glpi_softwares'))),

                        'title'
                           => array(array('to'     => 'usertitles_id',
                                          'tables' => array('glpi_users'))),

                        'type'
                           => array(array('to'     => 'cartridgeitemtypes_id',
                                          'tables' => array('glpi_cartridgeitems')),
                                    array('to'     => 'computertypes_id',
                                          'tables' => array('glpi_computers')),
                                    array('to'     => 'consumableitemtypes_id',
                                          'tables' => array('glpi_consumableitems')),
                                    array('to'     => 'contacttypes_id',
                                          'tables' => array('glpi_contacts')),
                                    array('to'     => 'devicecasetypes_id',
                                          'tables' => array('glpi_devicecases')),
                                    array('to'     => 'devicememorytypes_id',
                                          'tables' => array('glpi_devicememories')),
                                    array('to'     => 'suppliertypes_id',
                                          'tables' => array('glpi_suppliers')),
                                    array('to'     => 'monitortypes_id',
                                          'tables' => array('glpi_monitors')),
                                    array('to'     => 'networkequipmenttypes_id',
                                          'tables' => array('glpi_networkequipments')),
                                    array('to'     => 'peripheraltypes_id',
                                          'tables' => array('glpi_peripherals')),
                                    array('to'     => 'phonetypes_id',
                                          'tables' => array('glpi_phones')),
                                    array('to'     => 'printertypes_id',
                                          'tables' => array('glpi_printers')),
                                    array('to'     => 'softwarelicensetypes_id',
                                          'tables' => array('glpi_softwarelicenses')),
                                    array('to'     => 'usercategories_id',
                                          'tables' => array('glpi_users')),
                                    array('to'     => 'itemtype',
                                          'tables' => array('glpi_computers_items',
                                                            'glpi_displaypreferences'))),

                        'update_software'
                           => array(array('to'     => 'softwares_id',
                                          'tables' => array('glpi_softwares'))),

                        'use_version'
                           => array(array('to'     => 'softwareversions_id_use',
                                          'tables' => array('glpi_softwarelicenses'))),

                        'vID'
                           => array(array('to'     => 'softwareversions_id',
                                          'tables' => array('glpi_computers_softwareversions'))),

                        'conpta_num'
                           => array(array('to'     => 'accounting_number',
                                          'tables' => array('glpi_contracts'))),

                        'num_commande'
                           => array(array('to'     => 'order_number',
                                          'tables' => array('glpi_infocoms'))),

                        'bon_livraison'
                           => array(array('to'     => 'delivery_number',
                                          'tables' => array('glpi_infocoms'))),

                        'num_immo'
                           => array(array('to'     => 'immo_number',
                                          'tables' => array('glpi_infocoms'))),

                         'facture'
                           => array(array('to'     => 'bill',
                                          'tables' => array('glpi_infocoms'))),

                         'amort_time'
                           => array(array('to'     => 'sink_time',
                                          'tables' => array('glpi_infocoms'))),

                         'amort_type'
                           => array(array('to'     => 'sink_type',
                                          'tables' => array('glpi_infocoms'))),

                         'ifmac'
                           => array(array('to'     => 'mac',
                                          'tables' => array('glpi_networkequipments'))),

                         'ifaddr'
                           => array(array('to'     => 'ip',
                                          'tables' => array('glpi_networkequipments',
                                                            'glpi_networkports'))),

                         'ramSize'
                           => array(array('to'     => 'memory_size',
                                          'tables' => array('glpi_printers'))),

                         'ramSize'
                           => array(array('to'     => 'memory_size',
                                          'tables' => array('glpi_printers'))),

                         'facturation'
                           => array(array('to'     => 'billing',
                                          'tables' => array('glpi_contracts'))),

                         'monday'
                           => array(array('to'     => 'use_monday',
                                          'tables' => array('glpi_contracts'))),

                         'saturday'
                           => array(array('to'     => 'use_saturday',
                                          'tables' => array('glpi_contracts'))),

                         'recursive'
                           => array(array('to'     => 'is_recursive',
                                          'tables' => array('glpi_networkequipments', 'glpi_groups',
                                                            'glpi_contracts', 'glpi_contacts',
                                                            'glpi_suppliers', 'glpi_printers',
                                                            'glpi_softwares', 'glpi_softwareversions',
                                                            'glpi_softwarelicences'))),

                         'faq'
                           => array(array('to'     => 'is_faq',
                                          'tables' => array('glpi_knowbaseitems'))),

                         'flags_micro'
                           => array(array('to'     => 'have_micro',
                                          'tables' => array('glpi_monitors'))),

                         'flags_speaker'
                           => array(array('to'     => 'have_speaker',
                                          'tables' => array('glpi_monitors'))),

                         'flags_subd'
                           => array(array('to'     => 'have_subd',
                                          'tables' => array('glpi_monitors'))),

                         'flags_bnc'
                           => array(array('to'     => 'have_bnc',
                                          'tables' => array('glpi_monitors'))),

                         'flags_dvi'
                           => array(array('to'     => 'have_dvi',
                                          'tables' => array('glpi_monitors'))),

                         'flags_pivot'
                           => array(array('to'     => 'have_pivot',
                                          'tables' => array('glpi_monitors'))),

                         'flags_hp'
                           => array(array('to'     => 'have_hp',
                                          'tables' => array('glpi_phones'))),

                         'flags_casque'
                           => array(array('to'     => 'have_headset',
                                          'tables' => array('glpi_phones'))),

                         'flags_usb'
                           => array(array('to'    => 'have_usb',
                                          'tables' => array('glpi_printers'))),

                         'flags_par'
                           => array(array('to'    => 'have_parallel',
                                          'tables' => array('glpi_printers'))),

                         'flags_serial'
                           => array(array('to'       => 'have_serial',
                                          'tables' => array('glpi_printers'))),

                         'initial_pages'
                           => array(array('to'      => 'init_pages_counter',
                                          'tables' => array('glpi_printers'))),

                         'global'
                           => array(array('to'       => 'is_global',
                                          'tables'   => array('glpi_monitors', 'glpi_networkequipments',
                                                              'glpi_peripherals', 'glpi_phones',
                                                              'glpi_printers', 'glpi_softwares'))),
                                          'template' => array(array('to'     =>'template_name',
                                                                    'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                                                      'glpi_consumableitems', 'glpi_devicecases',
                                                                                      'glpi_devicecontrols', 'glpi_devicedrives',
                                                                                      'glpi_devicegraphiccards', 'glpi_deviceharddrives',
                                                                                      'glpi_devicenetworkcards', 'glpi_devicemotherboards',
                                                                                      'glpi_devicepcis', 'glpi_devicepowersupplies',
                                                                                      'glpi_deviceprocessors', 'glpi_devicememories',
                                                                                      'glpi_devicesoundcards', 'glpi_monitors',
                                                                                      'glpi_networkequipments', 'glpi_peripherals',
                                                                                      'glpi_phones', 'glpi_printers',
                                                                                      'glpi_softwares'))),
                                          'comments' => array(array('to'      => 'comment',
                                                                    'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                                                      'glpi_consumableitems', 'glpi_contacts',
                                                                                      'glpi_contracts', 'glpi_documents',
                                                                                      'glpi_autoupdatesystems', 'glpi_budgets',
                                                                                      'glpi_cartridgeitemtypes', 'glpi_devicecasetypes',
                                                                                      'glpi_consumableitemtypes', 'glpi_contacttypes',
                                                                                      'glpi_contracttypes', 'glpi_domains', 'glpi_suppliertypes',
                                                                                      'glpi_filesystems', 'glpi_networkequipmentfirmwares',
                                                                                      'glpi_networkinterfaces', 'glpi_interfacetypes',
                                                                                      'glpi_knowbaseitemcategories', 'glpi_softwarelicensetypes',
                                                                                      'glpi_locations', 'glpi_manufacturers','glpi_computermodels',
                                                                                      'glpi_monitormodels', 'glpi_networkequipmentmodels',
                                                                                      'glpi_peripheralmodels', 'glpi_phonemodels',
                                                                                      'glpi_printermodels', 'glpi_netpoints', 'glpi_networks',
                                                                                      'glpi_operatingsystems', 'glpi_operatingsystemservicepacks',
                                                                                      'glpi_operatingsystemversions', 'glpi_phonepowersupplies',
                                                                                      'glpi_devicememorytypes', 'glpi_documentcategories',
                                                                                      'glpi_softwarecategories', 'glpi_states', 'glpi_ticketcategories',
                                                                                      'glpi_usertitles', 'glpi_usercategories', 'glpi_vlans',
                                                                                      'glpi_suppliers', 'glpi_entities', 'glpi_groups', 'glpi_infocoms',
                                                                                      'glpi_monitors', 'glpi_phones', 'glpi_printers', 'glpi_peripherals',
                                                                                      'glpi_networkequipments', 'glpi_reservationitems', 'glpi_rules',
                                                                                      'glpi_softwares', 'glpi_softwarelicenses', 'glpi_softwareversions',
                                                                                      'glpi_computertypes', 'glpi_monitortypes', 'glpi_networkequipmenttypes',
                                                                                      'glpi_peripheraltypes', 'glpi_phonetypes','glpi_printertypes',
                                                                                      'glpi_users')),),

                           'notes' =>  array(array('to'     => 'notepad',
                                                   'tables' => array('glpi_cartridgeitems', 'glpi_computers',
                                                                     'glpi_consumableitems', 'glpi_contacts',
                                                                     'glpi_contracts', 'glpi_documents',
                                                                     'glpi_suppliers', 'glpi_entitydatas',
                                                                     'glpi_printers', 'glpi_monitors',
                                                                     'glpi_phones', 'glpi_peripherals',
                                                                     'glpi_networkequipments',
                                                                     'glpi_softwares'))));

    $foreignkeys = doHookFunction("plugin_datainjection_migratefields",$foreignkeys);
    $query = "SELECT `itemtype`, `value`
              FROM `glpi_plugin_datainjection_mappings`
              WHERE `itemtype` NOT IN ('none')
              GROUP BY `itemtype`,`value`";

    foreach ($DB->request($query) as $data) {
       if (isset($foreignkeys[$data['value']])) {
          foreach ($foreignkeys[$data['value']] as $field_info) {
             $table = getTableForItemType($data['itemtype']);

             if (in_array($table,$field_info['tables'])) {
                $query = "UPDATE `glpi_plugin_datainjection_mappings`
                          SET `value` = '".$field_info['to']."'
                          WHERE `itemtype` = '".$data['itemtype']."'
                                AND `value` = '".$data['value']."'";
                $DB->query($query) or die ("Datainjection : error converting mapping fields");

                $query = "UPDATE `glpi_plugin_datainjection_infos`
                          SET `value` = '".$field_info['to']."'
                          WHERE `itemtype` = '".$data['itemtype']."'
                                AND `value` = '".$data['value']."'";
                $DB->query($query) or die ("Datainjection : error converting infos fields");
            }
         }
      }
   }

/*
    $otherfields = array('comments' => 'comment', 'notes'=>'notepad');
    foreach ($otherfields as $old => $new) {
               $query = "UPDATE `glpi_plugin_datainjection_mappings`
                         SET `value`='".$new."'
                         WHERE `value`='".$old."'";
               $DB->query($query) or die ("Datainjection : error converting mapping field $old");
               $query = "UPDATE `glpi_plugin_datainjection_infos`
                         SET `value`='".$new."'
                         WHERE `value`='".$old."'";
               $DB->query($query) or die ("Datainjection : error converting infos field $old");
    }*/
}


function plugin_datainjection_createaccess($ID) {
   global $DB;

   $Profile = new Profile();
   $Profile->GetfromDB($ID);
   $name    = $Profile->fields["name"];

   $query = "INSERT INTO `glpi_plugin_datainjection_profiles`
                    (`ID`, `name` , `is_default`, `model`)
             VALUES ('$ID', '$name','0',NULL)";

   $DB->query($query);
}


function plugin_datainjection_checkRight($module, $right) {
   global $CFG_GLPI;

   if (!plugin_datainjection_haveRight($module, $right)) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }

      displayRightError();
   }
}


function plugin_datainjection_loadHook($hook_name, $params = array ()) {
   global $PLUGIN_HOOKS;

   if (!empty ($params)) {
      $type = $params["type"];
      //If a plugin type is defined
      doOneHook($PLUGIN_HOOKS['plugin_types'][$type], 'datainjection_' . $hook_name);

   } else {
      if (isset ($PLUGIN_HOOKS['plugin_types'])) {
         //Browse all plugins
         foreach ($PLUGIN_HOOKS['plugin_types'] as $type => $name) {
            doOneHook($name, 'datainjection_' . $hook_name);
         }
      }
   }
}


function plugin_datainjection_needUpdateOrInstall() {

   //Install plugin
   if (!TableExists('glpi_plugin_datainjection_profiles')
       && !TableExists('glpi_plugin_datainjection_profiles')) {
      return 0;
   }

   if (TableExists("glpi_plugin_datainjection_modelcsvs")) {
      return -1;
   }

   return 1;
}


function plugin_datainjection_giveItem($type, $ID, $data, $num) {
   global $DB, $CFG_GLPI, $LANG;

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_datainjection_models.port_unicity" :
         return PluginDatainjectionDropdown::getPortUnitictyValues($data['ITEM_'.$num]);

      case "glpi_plugin_datainjection_models.float_format" :
         return PluginDatainjectionDropdown::getFloatFormat($data['ITEM_'.$num]);
   }
   return "";
}

?>