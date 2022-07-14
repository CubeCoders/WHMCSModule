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