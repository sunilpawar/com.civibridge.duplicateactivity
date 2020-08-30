<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Duplicateactivity_Form_Task_Duplicateactivity extends CRM_Activity_Form_Task {
  /**
   * The id of activity type.
   *
   * @var int
   */
  public $_activityTypeId;

  public $_activityId;

  public $_values;

  /**
   * Maximum Activities that should be allowed to update.
   */
  protected $_maxActivities = 100;

  /**
   * Variable to store redirect path.
   */
  protected $_userContext;

  function preProcess() {

    // initialize the task and row fields.
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    $validate = FALSE;
    //validations
    if (count($this->_activityHolderIds) > $this->_maxActivities) {
      CRM_Core_Session::setStatus(ts("The maximum number of Activities you can select to Duplicate it is %1. You have selected %2. Please select fewer Activities from your search results and try again.", [
        1 => $this->_maxActivities,
        2 => count($this->_activityHolderIds),
      ]), ts("Maximum Exceeded"), "error");
      $validate = TRUE;
    }
    // then redirect
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }

    // hack to retrieve activity type id from post variables
    if (!$this->_activityTypeId) {
      $this->_activityTypeId = CRM_Utils_Array::value('activity_type_id', $_POST);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      // Need to assign custom data subtype to the template.
      $this->set('type', 'Activity');
      $this->set('subType', $this->_activityTypeId);
      $this->set('entityId', $this->_activityId);
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_activityTypeId, 1, 'Activity');
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = [];
      if (isset($this->_activityId) && $this->_activityId) {
        $params = ['id' => $this->_activityId];
        CRM_Activity_BAO_Activity::retrieve($params, $this->_values);
      }
      $this->set('values', $this->_values);
    }
  }

  function buildQuickForm() {

    $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
    // remove unwanted activity type from list.
    $activityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);

    $this->add('select', 'activity_type_id', ts('New Activity Type'),
      ['' => '- ' . ts('select') . ' -'] + $activityTypes,
      FALSE, [
        'onchange' => "CRM.buildCustomData( 'Activity', this.value );",
        'class' => 'crm-select2 required',
      ]
    );

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Activity');
    $this->assign('customDataSubType', $this->_activityTypeId);
  }

  function postProcess() {
    $values = $this->exportValues();
    foreach ($this->_activityHolderIds as $activityId) {
      // get data of each activity
      $params = ['id' => $activityId];
      CRM_Activity_BAO_Activity::retrieve($params, $activityValues);
      unset($activityValues['id']);
      unset($activityValues['activity_id']);

      $activityValues['target_contact_id'] = $activityValues['target_contact'];
      $activityValues['assignee_contact_id'] = $activityValues['assignee_contact'];
      $finalValues = array_merge($activityValues, $values);
      if (!empty($finalValues['hidden_custom']) && !isset($finalValues['custom'])) {
        $customFields = CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
          $finalValues['activity_type_id']
        );
        $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
          CRM_Core_BAO_CustomField::getFields('Activity', FALSE, FALSE,
            NULL, NULL, TRUE
          )
        );
        $finalValues['custom'] = CRM_Core_BAO_CustomField::postProcess($finalValues, NULL, 'Activity');
      }
      $finalValues['activity_date_time'] = CRM_Utils_Date::isoToMysql($finalValues['activity_date_time']);
      // create new activity record with new type
      $activity = CRM_Activity_BAO_Activity::create($finalValues);
    }
    CRM_Core_Session::setStatus(ts("Activities get Duplicated."), ts("Activities get Duplicated."), "success");
    CRM_Utils_System::redirect($this->_userContext);
  }

}