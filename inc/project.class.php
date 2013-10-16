<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Project Class
**/
class Project extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   static protected $forward_entity_to = array('ProjectTask');
   static $rightname = 'project';
   
   const READMY      = 1;
   const READALL     = 1024;

   protected $team   = array();
   
   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Project','Projects',$nb);
   }

   static function canView() {
      return Session::haveRightsOr(self::$rightname, array(self::READALL, self::READMY));
   }

   /**
    * Is the current user have right to create the current change ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight(self::$rightname, CREATE);
   }
   
   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[READ]);

      $values[self::READALL] = __('See all');
      $values[self::READMY]  = __('See (author)');

      return $values;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = array();
               $ong[2] = __('GANTT');
               return $ong;
         }
      } 

      return '';
   }
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {

               case 2 :
                     $item->showGantt($item->getID());
                  break;
            }
            break;
      }
      return true;
   }

   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('ProjectTask', $ong, $options);
      $this->addStandardTab('ProjectTeam', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Change_Project', $ong, $options);
      $this->addStandardTab('Item_Project', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);      
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }
   
   function post_getEmpty() {
      $this->fields['priority'] = 3;
      $this->fields['percent_done'] = 0;
   }
   
   function post_getFromDB() {
      // Team
      $this->team    = ProjectTeam::getTeamFor($this->fields['id']);
   }
   
   /// Get team member count
   function getTeamCount() {
      $nb = 0;
      if (is_array($this->team) && count($this->team)) {
         foreach ($this->team as $val) {
            $nb +=  count($val);
         }
      }
      return $nb;
   }
   
   function getSearchOptions() {

      $tab = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false; // implicit key==1

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false; // implicit field is id
      $tab[2]['datatype']        = 'number';

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'code';
      $tab[4]['name']          = __('Code');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'string';
      
      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'name';
      $tab[13]['name']              = __('Father');
      $tab[13]['datatype']          = 'dropdown';
      $tab[13]['massiveaction']     = false;
      // Add virtual condition to relink table
      $tab[13]['joinparams']        = array('condition' => "AND 1=1");

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['name']          = __('Description');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'text';

      $tab[3]['table']          = $this->getTable();
      $tab[3]['field']          = 'priority';
      $tab[3]['name']           = __('Priority');
      $tab[3]['searchtype']     = 'equals';
      $tab[3]['datatype']      = 'specific';

      $tab[14]['table']          = 'glpi_projecttypes';
      $tab[14]['field']          = 'name';
      $tab[14]['name']           = __('Type');
      $tab[14]['datatype']      = 'dropdown';

      $tab[12]['table']          = 'glpi_projectstates';
      $tab[12]['field']          = 'name';
      $tab[12]['name']           = __('State');
      $tab[12]['datatype']      = 'dropdown';
      
      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'date';
      $tab[15]['name']          = __('Opening date');
      $tab[15]['datatype']      = 'datetime';
      $tab[15]['massiveaction'] = false;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'percent_done';
      $tab[5]['name']          = __('Percent done');
      $tab[5]['datatype']      = 'number';
      $tab[5]['unit']          = '%';
      $tab[5]['min']           = 0;
      $tab[5]['max']           = 100;
      $tab[5]['step']          = 5;

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'show_on_global_gantt';
      $tab[6]['name']          = __('Show on global GANTT');
      $tab[6]['datatype']      = 'bool';

      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id';
      $tab[24]['name']           = __('Manager');
      $tab[24]['datatype']       = 'dropdown';
      $tab[24]['right']          = 'see_project';

      $tab[49]['table']          = 'glpi_groups';
      $tab[49]['field']          = 'completename';
      $tab[49]['linkfield']      = 'groups_id';
      $tab[49]['name']           = __('Manager group');
      $tab[49]['condition']      = '`is_manager`';
      $tab[49]['datatype']       = 'dropdown';
      
      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'plan_start_date';
      $tab[7]['name']          = __('Planned begin date');
      $tab[7]['datatype']      = 'datetime';

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'plan_end_date';
      $tab[8]['name']          = __('Planned end date');
      $tab[8]['datatype']      = 'datetime';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'real_start_date';
      $tab[9]['name']          = __('Real begin date');
      $tab[9]['datatype']      = 'datetime';

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'real_end_date';
      $tab[10]['name']          = __('Real end date');
      $tab[10]['datatype']      = 'datetime';
      
      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';
      
      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'notepad';
      $tab[90]['name']           = __('Notes');
      $tab[90]['massiveaction']  = false;
      $tab[90]['datatype']       = 'text';
      
      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';
      
      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      return $tab;
   }


   /**
    * @param $output_type     (default 'Search::HTML_OUTPUT')
    * @param $mass_id         id of the form to check all (default '')
    */
   static function commonListHeader($output_type=Search::HTML_OUTPUT, $mass_id='') {

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num                      = 1;

      $items                           = array();
      $items[(empty($mass_id)?'&nbsp':Html::getCheckAllAsCheckbox($mass_id))] = '';
      $items[__('Status')]             = "glpi_projectstates.name";
      $items[__('Date')]               = "date";
      $items[__('Last update')]        = "date_mod";

      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $items[_n('Entity', 'Entities', 2)] = "glpi_entities.completename";
      }

      $items[__('Priority')]         = "priority";
      $items[__('Manager')]          = "users_id";
      $items[__('Manager group')]    = "groups_id";
      $items[__('Name')]              = "name";

      foreach ($items as $key => $val) {
         $issort = 0;
         $link   = "";
         echo Search::showHeaderItem($output_type,$key,$header_num,$link);
      }

      // End Line for column headers
      echo Search::showEndLine($output_type);
   }

   /**
    * Display a line for an object
    *
    * @since version 0.85 (befor in each object with differents parameters)
    *
    * @param $id                 Integer  ID of the object
    * @param $options            array of options
    *      output_type            : Default output type (see Search class / default Search::HTML_OUTPUT)
    *      row_num                : row num used for display
    *      type_for_massiveaction : itemtype for massive action
    *      id_for_massaction      : default 0 means no massive action
    *      followups              : only for Tickets : show followup columns
    */
   static function showShort($id, $options=array()) {
      global $CFG_GLPI, $DB;


      $p['output_type']            = Search::HTML_OUTPUT;
      $p['row_num']                = 0;
      $p['type_for_massiveaction'] = 0;
      $p['id_for_massiveaction']   = 0;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $item         = new static();

      $candelete   = static::canDelete();
      $canupdate   = Session::haveRight(static::$rightname, UPDATE);
      $align       = "class='center";
      $align_desc  = "class='left";


      $align      .= "'";
      $align_desc .= "'";

      if ($item->getFromDB($id)) {
         $item_num = 1;
         $bgcolor  = $_SESSION["glpipriority_".$item->fields["priority"]];

         echo Search::showNewLine($p['output_type'],$p['row_num']%2);

         $check_col = '';
         if (($candelete || $canupdate)
             && ($p['output_type'] == Search::HTML_OUTPUT)
             && $p['id_for_massiveaction']) {

            $check_col = Html::getMassiveActionCheckBox($p['type_for_massiveaction'], $p['id_for_massiveaction']);
         }
         echo Search::showItem($p['output_type'], $check_col, $item_num, $p['row_num'], $align);

         // First column
         $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $item->fields["id"]);

         if ($item->fields["projectstates_id"]) {
            $first_col = sprintf(__('%1$s - %2$s'), $first_col,
                                 Dropdown::getDropdownName('glpi_projectstates', $item->fields["projectstates_id"]));
         }
         echo Search::showItem($p['output_type'], $first_col, $item_num, $p['row_num'], $align);

         // Second column
         $second_col = sprintf(__('Opened on %s'),
                                 ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                 Html::convDateTime($item->fields['date']));

         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align." width=130");

         // Second BIS column
         $second_col = Html::convDateTime($item->fields["date_mod"]);
         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align." width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            $second_col = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'],
                                  $align." width=100");
         }

         // Third Column
         echo Search::showItem($p['output_type'],
                               "<span class='b'>".CommonITILObject::getPriorityName($item->fields["priority"]).
                                 "</span>",
                               $item_num, $p['row_num'], "$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";

         if ($item->fields["users_id"]) {
            $userdata    = getUserName($item->fields["users_id"], 2);
            $fourth_col .= sprintf(__('%1$s %2$s'),
                                    "<span class='b'>".$userdata['name']."</span>",
                                    Html::showToolTip($userdata["comment"],
                                                      array('link'    => $userdata["link"],
                                                            'display' => false)));
         }

         echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);

         // Fifth column
         $fifth_col = "";

         if ($item->fields["groups_id"]) {
            $fifth_col .= Dropdown::getDropdownName("glpi_groups", $item->fields["groups_id"]);
            $fifth_col .= "<br>";
         }

         echo Search::showItem($p['output_type'], $fifth_col, $item_num, $p['row_num'], $align);


         // Eigth column
         $eigth_column = "<span class='b'>".$item->fields["name"]."</span>&nbsp;";

         // Add link
         if ($item->canViewItem()) {
            $eigth_column = "<a id='".$item->getType().$item->fields["id"]."$rand' href=\"".$item->getLinkURL()
                              ."\">$eigth_column</a>";
         }

         if ($p['output_type'] == Search::HTML_OUTPUT) {
            $eigth_column = sprintf(__('%1$s %2$s'), $eigth_column,
                                    Html::showToolTip($item->fields['content'],
                                                      array('display' => false,
                                                            'applyto' => $item->getType().$item->fields["id"].
                                                                           $rand)));
         }

         echo Search::showItem($p['output_type'], $eigth_column, $item_num, $p['row_num'],
                               $align_desc."width='200'");



         // Finish Line
         echo Search::showEndLine($p['output_type']);
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No item in progress.')."</i></td></tr>";
      }
   }


   function prepareInputForUpdate($input) {

      return self::checkPlanAndRealDates($input);
   }


   static function checkPlanAndRealDates($input) {

      if (isset($input['plan_start_date']) && isset($input['plan_end_date'])
         && !empty($input['plan_end_date'])
         && ($input['plan_end_date'] < $input['plan_start_date']
               || empty($input['plan_start_date']))) {
         Session::addMessageAfterRedirect(__('Invalid planned dates. Dates not updated.'), false, ERROR);
         unset($input['plan_start_date']);
         unset($input['plan_end_date']);
      }
      if (isset($input['real_start_date']) && isset($input['real_end_date'])
         && !empty($input['real_end_date'])
         && ($input['real_end_date'] < $input['real_start_date']
               || empty($input['real_start_date']))) {
         Session::addMessageAfterRedirect(__('Invalid real dates. Dates not updated.'), false, ERROR);
         unset($input['real_start_date']);
         unset($input['real_end_date']);
      }
      return $input;
   }
   /**
    * Print the computer form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Opening date')."</td>";
      echo "<td>";

      $date = $this->fields["date"];
      if (!$ID) {
         $date = $_SESSION['glpi_currenttime'];
      }
      Html::showDateTimeField("date", array('value'      => $date,
                                            'timestep'   => 1,
                                            'maybeempty' => false));
      echo "</td>";
      if ($ID) {
         echo "<td>".__('Last update')."</td>";
         echo "<td >";
         echo Html::convDateTime($this->fields["date_mod"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'name');
      echo "</td>";
      echo "<td>".__('Code')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'code');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Priority')."</td>";
      echo "<td>";
      CommonITILObject::dropdownPriority(array('value' => $this->fields['priority']));
      echo "</td>";
      echo "<td>".__('As child of')."</td>";
      echo "<td>";
      $this->dropdown(array('comments' => 0,
                            'entity'   => $this->fields['entities_id'],
                            'value'    => $this->fields['projects_id'],
                            'used'     => array($this->fields['id'])));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('State')."</td>";
      echo "<td>";
      ProjectState::dropdown(array('value' => $this->fields["projectstates_id"]));
      echo "</td>";
      echo "<td>".__('Percent done')."</td>";
      echo "<td>";
      Dropdown::showNumber("percent_done", array('value' => $this->fields['percent_done'],
                                                   'min'   => 0,
                                                   'max'   => 100,
                                                   'step'  => 5,
                                                   'unit'  => '%'));

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ProjectType::dropdown(array('value' => $this->fields["projecttypes_id"]));
      echo "</td>";
      echo "<td>".__('Show on global GANTT')."</td>";
      echo "<td>";
      Dropdown::showYesNo("show_on_global_gantt", $this->fields["show_on_global_gantt"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan='4' class='subheader'>".__('Manager')."</td></tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id',
                           'value'  => $this->fields["users_id"],
                           'right'  => 'see_project',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id',
                            'value'     => $this->fields['groups_id'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_manager`'));
      
      echo "</td></tr>\n";      
      echo "<tr><td colspan='4' class='subheader'>".__('Planning')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_start_date", array('value' => $this->fields['plan_start_date']));
      echo "</td>";
      echo "<td>".__('Real start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_start_date", array('value' => $this->fields['real_start_date']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_end_date", array('value' => $this->fields['plan_end_date']));
      echo "</td>";
      echo "<td>".__('Real end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_end_date", array('value' => $this->fields['real_end_date']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned duration')."</td>";
      echo "<td>";
      echo Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($this->fields['id']), false);
      echo "</td>";
      echo "<td>".__('Effective duration')."</td>";
      echo "<td>";
      echo Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($this->fields['id']), false);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Description')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='content' name='content' cols='90' rows='6'>".
               $this->fields["content"]."</textarea>";      
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='comment' name='comment' cols='90' rows='6'>".
               $this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>\n";
      
      $this->showFormButtons($options);

      return true;
   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'priority':
            return CommonITILObject::getPriorityName($values[$field]);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }
   /**
    * @since version 0.85
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'priority' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return CommonITILObject::dropdownPriority($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Show team for a project
   **/
   function showTeam(Project $project) {
      global $DB, $CFG_GLPI;

      $ID      = $project->fields['id'];
      $canedit = $project->canEdit($ID);

      echo "<div class='center'>";

      $rand = mt_rand();
      $nb = 0;

      $nb = $project->getTeamCount();

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='projectteam_form$rand' id='projectteam_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('ProjectTeam')."'>";
         echo "<input type='hidden' name='projects_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a team member')."</tr>";
         echo "<tr class='tab_bg_2'><td>";

         $params = array('itemtypes' => ProjectTeam::$available_types,
                         'entity_restrict' => ($project->fields['is_recursive']
                                               ? getSonsOf('glpi_entities', $project->fields['entities_id'])
                                               : $project->fields['entities_id']),
                         );
         $addrand = Dropdown::showSelectItemFromItemtypes($params);

         echo "</td>";
         echo "<td width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\"
               class='submit'>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = array('num_displayed'
                        => $nb,
                    'container'
                        => 'mass'.__CLASS__.$rand);
//                     'specific_actions'
//                         => array('MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.'delete'
//                                     => _x('button', 'Delete permanently')) );
// 
//          if ($this->fields['users_id'] != Session::getLoginUserID()) {
//             $massiveactionparams['confirm']
//                = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
//          }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      if ($canedit && $nb) {
         echo "<th width='10'>";
         echo Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
      }
      echo "<th>".__('Type')."</th>";
      echo "<th>"._n('Member', 'Members', 2)."</th>showGantt";
      echo "</tr>";

      foreach (ProjectTeam::$available_types as $type) {
         if (isset($project->team[$type]) && count($project->team[$type])) {
            if ($item = getItemForItemtype($type)) {
               foreach ($project->team[$type] as $data) {
                  $item->getFromDB($data['items_id']);
                  echo "<tr class='tab_bg_2'>";
                  if ($canedit) {
                     echo "<td>";
                     Html::showMassiveActionCheckBox('ProjectTeam',$data["id"]);
                     echo "</td>";
                  }
                  echo "<td>".$item->getTypeName(1)."</td>";
                  echo "<td>".$item->getLink()."</td>";
                  echo "</tr>";
               }
            }
         }
      }

      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] =false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }


   /** Get data to display on GANTT
   * @param $ID ID of the project
   */
   static function getDataToDisplayOnGantt($ID) {
      global $DB;

      $todisplay = array();
      $project = new Project();
      if ($project->getFromDB($ID)) {
         $projects = array();
         foreach ($DB->request('glpi_projects', array('projects_id' => $ID)) as $data) {
               $projects = static::getDataToDisplayOnGantt($data['id']);
         }
         ksort($projects);
         // Get all tasks
         $tasks = ProjectTask::getAllForProject($ID);
         
         $real_begin = NULL;
         $real_end = NULL;
         // Determine begin / end date of current project if not set (min/max sub projects / tasks)
         if (!is_null($project->fields['plan_start_date'])) {
            $real_begin = $project->fields['plan_start_date'];
         } else {
            foreach($tasks as $task) {
               if (is_null($real_begin) 
                     || (!is_null($task['plan_start_date'])
                           && $real_begin > $task['plan_start_date'])) {
                  $real_begin = $task['plan_start_date'];
               }
            }
            foreach($projects as $p) {
               if (is_null($real_begin)
                     || ($p['type']=='project'
                           &&!is_null($p['from'])
                           && $real_begin > $p['from'])) {
                  $real_begin = $p['from'];
               }
            }
         }
         // Use real if not found
         if (is_null($real_begin) && !is_null($project->fields['real_start_date'])) {
            $real_begin = $project->fields['real_start_date'];
         }

         if (!is_null($project->fields['plan_end_date'])) {
            $real_end = $project->fields['plan_end_date'];
         } else {
            foreach($tasks as $task) {
               if (is_null($real_end)
                     || (!is_null($task['plan_end_date'])
                           && $real_end < $task['plan_end_date'])) {
                  $real_end = $task['plan_end_date'];
               }
            }
            foreach($projects as $p) {
               if (is_null($real_end)
                     || ($p['type']=='project'
                           && !is_null($p['to'])
                           && $real_end < $p['to'])) {
                  $real_end = $p['to'];
               }
            }
         }
         // Use real if not found
         if (is_null($real_end) && !is_null($project->fields['real_end_date'])) {
            $real_end = $project->fields['real_end_date'];
         }
         
         // Add current project
         if (!is_null($real_begin) && !is_null($real_end)) {
            $todisplay[$real_begin.'#'.$real_end.'#'.$project->getID()]
                         = array('name'   => $project->fields['name'],
                                 'desc'   => '',
                                 'type'   => 'project',
                                 'from'   => $real_begin,
                                 'to'     => $real_end);
         }  else {
            echo sprintf(__('Unable to determine begin or start date of project %s'), $project->fields['name']);
            echo "<br>";
            return $todisplay;
         }

         // Add current tasks
//          static::getDataToDisplayOnGantt($data['id']);
         foreach($tasks as $task) {
            // Foreach task determine begin and end based on sub tasks
            /// TODO
         }

         // Add ordered subprojects
         foreach($projects as $key => $val) {
            $todisplay[$key] = $val;
         }
      }

      return $todisplay;
   }
   
   /** show GANTT diagram for a project or for all
   * @param $ID ID of the project or -1 for all projects
   */
   static function showGantt($ID) {
      global $DB;
      
      if ($ID>0) {
         $project = new Project();
         if ($project->getFromDB($ID) && $project->canView()) {
            $todisplay = static::getDataToDisplayOnGantt($ID);
//             echo json_encode($todisplay);
//             // Get all sub projects
//             
//             $projects = getSonsOf('glpi_projects', $ID);
//             foreach ($projects as $pID) {
//                $temp = array();
//                // Get all tasks
//                $tasks = array();
//                $tasks[$pID] = ProjectTask::getAllForProject($pID);
//             }
         } else {
            return false;
         }

      }

      if (count($todisplay)) {
         
         // Prepare for display
         $data = array();
         foreach ($todisplay as $key => $val) {
            $temp = array();
            switch ($val['type']) {
               case 'project':
                  $temp = array('name' => $val['name'],
                                'desc' => '',
                                'values' =>array(array('from'        => "/Date(".strtotime($val['from'])."000)/",
                                                  'to'          => "/Date(".strtotime($val['to'])."000)/",
                                                  'desc'        => '',
                                                  'label'       => $val['name'],
                                                  'customClass' => 'ganttRed'))
                               );
                  break;
               case 'task':
                  break;
                  

            }
            $data[] = $temp;
         }
//       Html::printCleanArray($data);
            
//       exit();
         echo "<div class='gantt'></div>";
         $js = "
                           $('.gantt').gantt({
                                     source: ".json_encode($data).",
//                                  source: [{
//                                           name: 'Sprint \'0',
//                                           desc: 'Analysis',
//                                           values: [{
//                                                    from: '/Date(1320192000000)/',
//                                                    to: '/Date(1322401600000)/',
//                                                    desc: 'desc',
//                                                    label: 'Requirement Gathering',
//                                                    customClass: 'ganttRed'
//                                           }]
//                                  },{
//                                           name: '',
//                                           desc: 'Scoping',
//                                           values: [{
//                                                    from: '/Date(1322611200000)/',
//                                                    to: '/Date(1323302400000)/',
//                                                    label: 'Scoping',
//                                                    customClass: 'ganttRed'
//                                           }]
//                                  },{
//                                           name: 'Sprint 1',
//                                           desc: 'Development',
//                                           values: [{
//                                                    from: '/Date(1323802400000)/',
//                                                    to: '/Date(1325685200000)/',
//                                                    label: 'Development',
//                                                    customClass: 'ganttGreen'
//                                           }]
//                                  },{
//                                           name: '',
//                                           desc: 'Showcasing',
//                                           values: [{
//                                                    from: '/Date(1325685200000)/',
//                                                    to: '/Date(1325695200000)/',
//                                                    label: 'Showcasing',
//                                                    customClass: 'ganttBlue'
//                                           }]
//                                  },{
//                                           name: 'Sprint 2',
//                                           desc: 'Development',
//                                           values: [{
//                                                    from: '/Date(1326785200000)/',
//                                                    to: '/Date(1325785200000)/',
//                                                    label: 'Development',
//                                                    customClass: 'ganttGreen'
//                                           }]
//                                  },{
//                                           name: 'Sprint 2',
//                                           desc: 'Showcasing',
//                                           values: [{
//                                                    from: '/Date(1328785200000)/',
//                                                    to: '/Date(1328905200000)/',
//                                                    label: 'Showcasing',
//                                                    customClass: 'ganttBlue'
//                                           }]
//                                  },{
//                                           name: 'Release Stage',
//                                           desc: 'Training',
//                                           values: [{
//                                                    from: '/Date(1330011200000)/',
//                                                    to: '/Date(1336611200000)/',
//                                                    label: 'Training',
//                                                    customClass: 'ganttOrange'
//                                           }]
//                                  },{
//                                           name: ' ',
//                                           desc: 'Deployment',
//                                           values: [{
//                                                    from: '/Date(1336611200000)/',
//                                                    to: '/Date(1338711200000)/',
//                                                    label: 'Deployment',
//                                                    customClass: 'ganttOrange'
//                                           }]
//                                  },{
//                                           name: ' ',
//                                           desc: 'Warranty Period',
//                                           values: [{
//                                                    from: '/Date(1336611200000)/',
//                                                    to: '/Date(1349711200000)/',
//                                                    label: 'Warranty Period',
//                                                    customClass: 'ganttOrange'
//                                           }]
//                                  }],
                                 navigate: 'scroll',
                                 maxScale: 'hours',
                                 itemsPerPage: 20,
                                 onItemClick: function(data) {
   //                                         alert('Item clicked - show some details');
                                 },
                                 onAddClick: function(dt, rowId) {
   //                                         alert('Empty space clicked - add an item!');
                                 },
                                 onRender: function() {
   //                                         if (window.console && typeof console.log === 'function') {
   //                                                 console.log('chart rendered');
   //                                         }
                                 }
                           });";
         echo Html::scriptBlock($js);
      } else {
         _e('Nothing to display');
      }
   }
}
?>