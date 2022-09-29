<?php

use WHMCS\Database\Capsule;

add_hook('EmailPreSend', 1, function($vars) {
    if($vars['service_id'])
    {
        $service = Capsule::table('ampServices')->where('serviceId', $vars['service_id'])->first();   
        $serverId = Capsule::table('tblhosting')->where('id', $vars['service_id'])->value('server');   
        
        $server = Capsule::table('tblservers')->where('id', $serverId)->first();
    
        $endpoint = (!empty( $server->hostname) ?  $server->hostname: $server->ipaddress);
        $endpoint =  (!empty($server->secure) ? 'https://' : 'http://' ). $endpoint ;
     
        $endpoint = $endpoint .  ((!empty($server->port) && $server->secure != true ) ? ':'.$params['serverport'] : '');
    
        $merge_fields = [];
        $merge_fields['ampEndpoints'] =  json_decode($service->endpoints, 1);

        $merge_fields['ampApplicationUrl'] = $endpoint . '/?instance='. $service->instanceId;
    
        return $merge_fields;
    }
});

if($_REQUEST['ampCallback'] == 1)
{
    $data = json_decode(file_get_contents('php://input'), 1);
    if($data['Success'] == true && !empty($data['Secret']) && !empty($data['TargetId']) && !empty($data['InstanceId']))
    {
        if($data['Action'] == 'Create')
        {
            Capsule::table('ampServices')->updateOrInsert(['secret' => $data['Secret'], 'instanceId' => ''], ['instanceId' => $data['InstanceId'], 'targetId' => $data['TargetId'], 'endpoints' => json_encode($data['Endpoints'])]);
                
            $serviceId = Capsule::table('ampServices')->where('secret', $data['Secret'])->value('serviceId');   
    
            if(!empty($serviceId))
            {
                $command = 'SendEmail';
                $postData = array(
                    'messagename' => 'AMP Welcome Email',
                    'id' => $serviceId
                );
                localAPI($command, $postData);
        
                Capsule::table('ampTasks')->where('serviceId', $serviceId)->delete();
            }
        }
        elseif($data['Action'] == 'Modify')
        {
            Capsule::table('ampServices')->where('secret', $data['Secret'])->where('instanceId', $data['InstanceId'])->update(
                ['endpoints' => json_encode($data['Endpoints'])]
            );      
   
        }
 

    }elseif($data['Success'] == false && $data['Secret'] && $data['Action'] == 'Create')
    {
        $serviceId = Capsule::table('ampServices')->where('secret', $data['Secret'])->value('serviceId');   
        if(!empty($serviceId))
        {
            $task =  Capsule::table('ampTasks')->where('serviceId', $serviceId)->first();
            if($task->tried == 0)
            {
                localAPI('ModuleCreate', [ 'serviceid' => $serviceId ]);
                Capsule::table('ampTasks')->where('id', $task->id)->update(['tried' => 1]);

            }elseif($task->tried >= 1)
            {
                $command = 'SendAdminEmail';
                $postData = array(
                    'customsubject' => 'AMP Deployment Template Callback failure',
                    'custommessage' => 'Unable to deploy template for service: '. $task->serviceId,
                    'type' => 'account'
                );

                localAPI($command, $postData);
                Capsule::table('ampTasks')->where('serviceId', $task->serviceId)->delete();
            }
        }
    }
    die;
}

add_hook('AdminAreaFooterOutput', 1, function($vars) {
    if(strpos($_SERVER['SCRIPT_NAME'], 'configproducts') === false)
        return;

    return <<<HTML
      <div id="extraProvisionSettingsMain" hidden>
    <form id="extraProvisionSettingsForm" >

    <div class="form-inline">

    <div class="form-group mb-2">
        <label for="url">Key</label>
        <input class="form-control" type="text" name="extraProvisionSettingsKey" id="extraProvisionSettingsKey" />
      </div>
      <div class="form-group mx-sm-3 mb-2">
        <label for="website">Value</label>
        <input class="form-control" type="text" name="extraProvisionSettingsValue" id="extraProvisionSettingsValue" />
      </div>
   
      <button class="btn btn-success">Add</button>
    
      </div>
    
  
    </form>
    <table id="extraProvisionSettingsTable" class="datatable">
      <thead>
        <tr>
          <th style="width:200px;">Key</th>
          <th style="width:200px;">Value</th>
          <th style="width:100px;">Action</th>
        </tr>
      </thead>
      <tbody id="extraProvisionSettingsTableBody"></tbody>
    </table>
    </div>
<div class="modal fade" id="extraProvisionSettingsModal" tabindex="-1" role="dialog" aria-labelledby="extraProvisionSettingsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header">
        <h1 class="modal-title">Extra Provision Settings         <button type="button" class="close" data-dismiss="modal">&times;</button></h1>

      </div>

      <div class="modal-body">

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Continue</button>
      </div>
    </div>
  </div>
</div>


    <script>
      clone = $('#extraProvisionSettingsMain').clone();
      $('#extraProvisionSettingsMain').remove();

      function tableToJson(table) { 
        var data = [];
        for (var i=1; i<table.rows.length; i++) { 
            var tableRow = table.rows[i]; 
            var rowData = []; 
            for (var j=0; j<tableRow.cells.length - 1; j++) { 
                rowData.push(tableRow.cells[j].innerHTML);; 
            } 
            data.push(rowData); 
        } 
        return data; 
      }

      function onAddWebsite(e) {
        e.preventDefault();
        key = $('#extraProvisionSettingsKey').val();
        $('#extraProvisionSettingsKey').val("");
        value = $('#extraProvisionSettingsValue').val();
        $('#extraProvisionSettingsValue').val("");

        if(key == "" || value == "")
        {
          return;
        }
        tbodyEl.innerHTML += '<tr><td>'+key+'</td><td>'+value+'</td><td><button class="btn btn-danger deleteBtn">Delete</button></td> </tr>';
        json = tableToJson(tableEl);
        $('[name="packageconfigoption[4]"]').val(JSON.stringify(json));
      }

      function onDeleteRow(e) {
        if (!e.target.classList.contains("deleteBtn")) {
          return;
        }

        btn = e.target;
        btn.closest("tr").remove();
        json = tableToJson(tableEl);
        $('[name="packageconfigoption[4]"]').val(JSON.stringify(json));
      }
    </script>
HTML;
});