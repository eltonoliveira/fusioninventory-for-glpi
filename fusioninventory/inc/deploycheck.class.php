<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2012 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Behaviors. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Walid Nouh
   @co-author
   @copyright Copyright (c) 2010-2012 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginFusioninventoryDeployCheck extends CommonDBTM {

   static function getTypeName($nb=0) {
      return __('Audits');
   }

   static function getTypes() {
      return array(
         'winkeyExists'     => __("winkeyExists"),
         'winkeyMissing'    => __("winkeyMissing"),
         'winkeyEquals'     => __("winkeyEquals"),
         'fileExists'       => __("fileExists"),
         'fileMissing'      => __("fileMissing"),
         'fileSizeGreater'  => __("fileSizeGreater"),
         'fileSizeEquals'   => __("fileSizeEquals"),
         'fileSizeLower'    => __("fileSizeLower"),
         'fileSHA512'       => __("fileSHA512"),
         'freespaceGreater' => __("freespaceGreater")
      );
   }

   static function displayForm($order_type, $packages_id, $datas, $rand) {
      global $CFG_GLPI;

      echo "<div style='display:none' id='checks_block$rand' >";

      echo "<span id='showCheckType$rand'></span>";
      echo "<script type='text/javascript'>";
      $params = array(
         'rand'    => $rand,
         'subtype' => "check"
      );
      Ajax::UpdateItemJsCode("showCheckType$rand",
                             $CFG_GLPI["root_doc"].
                             "/plugins/fusioninventory/ajax/deploydropdown_packagesubtypes.php",
                             $params,
                             "dropdown_deploy_checktype");
      echo "</script>";


      echo "<span id='showCheckValue$rand'></span>";
      
      echo "<hr>";
      echo "</div>";

      //display stored checks datas
      if (!isset($datas['jobs']['checks'])) return;
      echo "<table class='tab_cadre' style='width:100%'>";
      foreach ($datas['jobs']['checks'] as $check) {
         //specific case for filesystem size
         if (!empty($check['value']) && is_numeric($check['value'])) {
            $check['value'] = round($check['value'] / (1024 * 1024))." MB";
         }

         echo "<tr>";
         echo "<td><input type='checkbox' /></td>";
         echo "<td>".$check['type']."</td>";
         echo "<td>".$check['path']."</td>";
         echo "<td>".$check['value']."</td>";
         echo "</tr>";
      }
      echo "<tr><td colspan='2'>";
      echo "<input type='button'  name='delete' value=\"".
         __('Delete', 'fusioninventory')."\" class='submit'>";
      echo "</td></tr>";
      echo "</table>";
   }

   static function dropdownType($rand) {
      global $CFG_GLPI;

      $checks_types = self::getTypes();
      array_unshift($checks_types, "---");
      echo "<table class='package_item'>";
      echo "<tr>";
      echo "<th>".__("Type")."</th>";
      echo "<td>";
      Dropdown::showFromArray("deploy_checktype", $checks_types, array('rand' => $rand));
      echo "</td>";
      echo "</tr></table>";

      //ajax update of check value span
      $params = array(
                      'value'  => '__VALUE__',
                      'rand'   => $rand,
                      'myname' => 'method',
                      'type'   => "check");
      Ajax::updateItemOnEvent("dropdown_deploy_checktype".$rand,
                              "showCheckValue$rand",
                              $CFG_GLPI["root_doc"].
                              "/plugins/fusioninventory/ajax/deploy_displaytypevalue.php",
                              $params,
                              array("change", "load"));


   }

   static function displayAjaxValue($value, $rand) {
      $value_type = "input";
      switch ($value) {
         case "winkeyExists":
         case "winkeyMissing":
            $path_label = __("Key");
            $value_label = false;
            break;
         case "winkeyEquals":
            $path_label = __("Key");
            $value_label = "Key value";
            break;
         case "fileExists":
         case "fileMissing":
            $path_label = __("File");
            $value_label = false;
            break;
         case "fileSizeGreater":
         case "fileSizeEquals":
         case "fileSizeLower":
            $path_label = __("File");
            $value_label = "Value";
            $value_type = "input+unit";
            break;
         case "fileSHA512":
            $path_label = __("File");
            $value_label = "Value";
            $value_type = "textarea";
            break;
         case "freespaceGreater":
            $path_label = __("Disk or directory");
            $value_label = "Value";
            $value_type = "input+unit";
            break;
      }

      echo "<table class='package_item'>";
      echo "<tr>";
      echo "<th>$path_label</th>";
      echo "<td><input type='text' name='path' id='check_path$rand' /></td>";
      echo "</tr>";
      if ($value_label !== false) {
         echo "<tr>";
         echo "<th>$value_label</th>";
         switch ($value_type) {
            case "textarea":
               echo "<td><textarea name='value' id='check_value$rand' rows='5'></textarea></td>";
               break;
            case "input":
               echo "<td><input type='text' name='value' id='check_value$rand' /></td>";
               break;
            case "input+unit":
               echo "<td><input type='text' name='value' id='check_value$rand' /></td>";
               echo "</tr><tr>";
               echo "<th>".__("Unit")."</th>";
               echo "<td>";
               Dropdown::showFromArray('unit', array(
                  "MB" => __("MiB"),
                  "GB" => __("GiB")
               ));
               echo "</td>";
               break;

         }
         echo "</tr>";
      }

      echo "<tr><td></td><td>";
      echo "<input type='submit' name='itemaddcheck' value=\"".
         __('Add')."\" class='submit' >";
      echo "</td></tr>";

      echo "</table>";
      
   }

   /**
    * Get all checks for an order
    * @param orders_id the order ID
    * @return an array with all checks, or an empty array is nothing defined
    */
   static function getForOrder($orders_id) {
      $check = new self;
      $results = $check->find("`plugin_fusioninventory_deployorders_id`='$orders_id'", 
                              "ranking ASC");

      $checks = array();
      foreach ($results as $result) {
         $tmp = array();
         if (empty($result['type'])) continue;

         if (isset($result['match'])) {
            $tmp['match'] = $result['match'];
         }
         if ($result['value'] != "")   $tmp['value'] = $result['value'];
         if ($result['path'] != "")    $tmp['path'] = $result['path'];
         if ($result['type'] != "")    $tmp['type'] = $result['type'];

         $tmp['return'] = "error";

         if ($tmp['type'] == "fileSizeGreater" || $tmp['type'] == "fileSizeLower" 
               || $tmp['type'] == "fileSizeEquals") {
            # according to the requirment, We want Bytes!
            $tmp['value'] *= 1024 * 1024;
         }
         $checks[] = $tmp;
      }

      return $checks;
   }


}

?>