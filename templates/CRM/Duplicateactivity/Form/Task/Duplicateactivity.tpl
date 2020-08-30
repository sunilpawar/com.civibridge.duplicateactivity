<div class="crm-block crm-form-block crm-contact-task-duplicateactivity-form-block">
  <table class="form-layout">
    <tr class="crm-contact-task-addtogroup-form-block-group_id">
      <td class="label">{$form.activity_type_id.label}</td>
      <td>{$form.activity_type_id.html}</td>
    </tr>
      {if $action neq 4 OR $viewCustomData}
        <tr class="crm-activity-form-block-custom_data">
          <td colspan="2">
            {if $action eq 4}
              {include file="CRM/Custom/Page/CustomDataView.tpl"}
            {else}
              <div id="customData"></div>
            {/if}
          </td>
        </tr>
      {/if}
  </table>
  <table class="form-layout">
    <tr><td>{include file="CRM/Activity/Form/Task.tpl"}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{*include custom data js file*}
{include file="CRM/common/customData.tpl"}
{literal}
<script type="text/javascript">
    CRM.$(function($) {
        {/literal}
        {if $customDataSubType}
        CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
        {else}
        CRM.buildCustomData( '{$customDataType}' );
        {/if}
        {literal}
    });
</script>
{/literal}