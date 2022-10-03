<?php

use WHMCS\Database\Capsule;
use CubeCoders\AMP\Client;

require_once __DIR__.'/lib/Client.php';

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function AMP_MetaData()
{
    return [
        'DisplayName' => 'AMP',
        'RequiresServer' => true,
    ];
}

function AMP_ConfigOptions()
{
    try {
        if (!Capsule::schema()->hasTable('ampSessions')) {
            Capsule::schema()
                ->create(
                    'ampSessions',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('serverId');
                        $table->text('sessionId');
                    }
                ); 
        }

        if (!Capsule::schema()->hasTable('ampServices')) {
            Capsule::schema()
                ->create(
                    'ampServices',
                    function ($table) {
                        $table->increments('id');
                        $table->integer('serviceId');
                        $table->text('secret');
                        $table->text('targetId');
                        $table->text('instanceId');
                        $table->text('endpoints');
                    }
                ); 
        }else
        {
            if (!Capsule::schema()->hasColumn('ampServices', 'endpoints'))
            {
                Capsule::schema()->table('ampServices', function($table)
                {
                    $table->string('endpoints');
                });
            }
        }

        if (!Capsule::schema()->hasTable('ampTasks')) {
            Capsule::schema()
                ->create(
                    'ampTasks',
                    function ($table) {
                        $table->increments('id');
                        $table->text('serviceId');
                        $table->text('type');
                        $table->integer('tried');
                        $table->timestamp('created_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
                        $table->timestamp('updated_at')->default(Capsule::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
                    }
                ); 
        }

        $server = Capsule::table('tblservers')
        ->join('tblservergroupsrel', 'tblservergroupsrel.serverid', '=', 'tblservers.id')
        ->where('tblservergroupsrel.groupid', App::getFromRequest('servergroup'))
        ->where('tblservers.disabled', '0')
        ->where('tblservers.active', '1')
        ->first();

        $params['serverid'] = $server->id;
        $params['serverusername'] = $server->username;
        $params['serverpassword'] = decrypt($server->password);
        $params['serverhostname'] = $server->hostname;
        $params['serverip'] = $server->ipaddress;
        $params['serversecure'] = $server->secure;
        $params['serverport'] = $server->port;

        $client = new Client($params);
        $version = $client->version;

        $info = $client->call('Core/GetModuleInfo');
        $ampVersion = $info['result']['AMPVersion'];
        $featureSet = $info['result']['FeatureSet'];

        $exploded = explode('.', $ampVersion);

        if(count($exploded) == 1)
        {
            $ampVersion .= '.0.0.0';
        }elseif(count($exploded) == 2)
        {
            $ampVersion .= '.0.0';
        }
        elseif(count($exploded) == 3)
        {
            $ampVersion .= '.0';
        }
        elseif(count($exploded) > 4)
        {
            $notParsed = $ampVersion;
        }
        $errors = [];
        if($notParsed)
        {
            $errors[] = 'AMP reported as version '. $notParsed;
        }elseif($ampVersion < $version)
        {
            $errors[] = 'AMP version '.$version.' or newer is required to use this module, you are currently running '.$ampVersion;
        }
        $commercial = in_array("CommercialUsage", $featureSet);
		if (!$commercial)
        {
            $errors[] = 'AMP has not been configured for commercial usage';
        }
        $errors = implode('. ', $errors);


        if (App::getFromRequest('action') != 'save' && !$commercial) {
            throw new \Exception('AMP has not been configured for commercial usage');
        }

        $script = '';
        if(!empty($errors))
        {
            $script = '<script>$("#ampAlert").remove(); $("#tab3").prepend(\'<div id="ampAlert" class="alert alert-warning" role="alert">'.$errors.'</div>\')</script>';
        }
  

        $templates = $client->call('ADSModule/GetDeploymentTemplates');

        $options = [];
        foreach ($templates['result'] as $t) {
            $options[$t['Id']] = $t['Name'];
        }

        $scriptExtraProvisionSettings = '
        <script>
        $(\'[name="packageconfigoption[4]"]\').hide();
        clone.appendTo($(\'[name="packageconfigoption[4]"]\').parent()).show();

        
            formEl = document.getElementById("extraProvisionSettingsForm");
            tbodyEl = document.getElementById("extraProvisionSettingsTableBody");
            tableEl = document.getElementById("extraProvisionSettingsTable");
            formEl.addEventListener("submit", onAddWebsite);
            tableEl.addEventListener("click", onDeleteRow);

         
            try {
                a = JSON.parse($(\'[name="packageconfigoption[4]"]\').val());
                tbodyEl.innerHTML = "";
                for (const element of a) {
                    tbodyEl.innerHTML += \'<tr><td>\'+element[0]+\'</td><td>\'+element[1]+\'</td><td><button class="btn btn-danger deleteBtn">Delete</button></td> </tr>\';
                }  
                 } catch(e) {}

            </script>';
        $fields = [
            'Provisioning Template' => array(
                'Type' => 'dropdown',
                'Options' => $options,
                'Description' => 'Choose one',
            ),
            'Post Create Action' => array(
                'Type' => 'dropdown',
                'Options' => 
                [
                    0 => 'Do nothing',
                    10 => 'Start instance only',
                    20 => 'Start instance and update application',
                    30 => 'Full application startup',
                ],
                'Description' => 'Choose one'.$script,
            ),
            'Required Tags' => array(
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => 'comma separated',
            ),
            'Extra Provision Settings' => array(
                'Type' => 'text',
                'Size' => '25',
                'Default' => '',
                'Description' => $scriptExtraProvisionSettings,
            ),
             
        ];
        return $fields;

    } catch (\Exception $ex) {
        if (App::getFromRequest('action') != 'save') {
            throw new \Exception($ex->getMessage());
        }
    }
}

function AMP_TestConnection(array $params)
{
    $client = new Client($params);
    $version = $client->version;
    try {
        AMP_commercialCheck($params);
        $info = $client->call('Core/GetModuleInfo');
        $ampVersion = $info['result']['AMPVersion'];
        $exploded = explode('.', $ampVersion);

        if(count($exploded) == 1)
        {
            $ampVersion .= '.0.0.0';
        }elseif(count($exploded) == 2)
        {
            $ampVersion .= '.0.0';
        }
        elseif(count($exploded) == 3)
        {
            $ampVersion .= '.0';
        }
        elseif(count($exploded) > 4)
        {
            $notParsed = $ampVersion;
        }
        $errors = [];
        if($notParsed)
        {
            $errors[] = 'AMP reported as version '. $notParsed;
        }elseif($ampVersion < $version)
        {
            $errors[] = 'AMP version '.$version.' or newer is required to use this module, you are currently running '.$ampVersion;
        }
        $errors = implode('. ', $errors);

     
        if(!empty($errors))
        {
            return [
                'success' => false,
                'error' => $errors,
            ];
        }
          
        

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }

    return ['success' => true];
}

function AMP_CreateAccount(array $params)
{
    try {
        AMP_commercialCheck($params);
        $client = new Client($params);
        $provisioningTemplateId = $params['configoption1'];
        $postCreate = !empty($params['configoption2']) ? $params['configoption2'] : 10;
        $templates = $client->call('ADSModule/GetDeploymentTemplates');

        $options = [];
        foreach ($templates['result'] as $t) {
            if($t['Id'] == $provisioningTemplateId)
            {
                $templateName = $t['Name'];
                break;
            }
        }
        
        $t = str_replace("-", "", $params['clientsdetails']['uuid']);
        $username = str_replace('==','', base64_encode(pack("h*", $t)));

        $password = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

        Capsule::table('tblhosting')->updateOrInsert(['id' => $params['serviceid']], ['username' => $username, 'password' => encrypt($password)]);

        $array = json_decode($params['configoption4']);
        $extraProvisionSettings = [];
        foreach ($array as $key => $value) {
            $extraProvisionSettings[$value[0]] = $value[1];
        }

        $requiredTags = explode(',', trim($params['configoption3']));

        foreach ($params['configoptions'] as $key => $value) {
            if($key[0] == '+' || $key[0] == '$')
            {
                $extraProvisionSettings[$key] = $value;
            }
            elseif($key[0] == '@')
            {
                $requiredTags[] = $value;
            }
        }

        $data = [
            'TemplateID' => $provisioningTemplateId,
            'NewUsername' => $username,
            'NewPassword' => $password,
            'Tag' => $params['serviceid'],
            'FriendlyName' => '',
            'Secret' => 'secretwhmcs'. $params['serviceid'],
            'PostCreate' => $postCreate,
            'RequiredTags' => $requiredTags,
            'ExtraProvisionSettings' => $extraProvisionSettings
        ];

        Capsule::table('ampServices')->updateOrInsert(['serviceId' => $params['serviceid']], ['secret' => 'secretwhmcs'. $params['serviceid']]);
        $response = $client->call('ADSModule/DeployTemplate', $data);

        $task =  Capsule::table('ampTasks')->where('serviceId', $params['serviceid'])->first();
        if(empty($task))
        {
            Capsule::table('ampTasks')->updateOrInsert(['serviceId' => $params['serviceid']], ['type' => 'redeploy', 'tried' => 0]);
        }

       
        


        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_TerminateAccount(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId
        ];
        $client->call('ADSModule/DeleteInstance', $data);
        Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->delete();
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_SuspendAccount(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId,
            'Suspended' => true
        ];
        $client->call('ADSModule/SetInstanceSuspended', $data);
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_UnsuspendAccount(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId,
            'Suspended' => false
        ];
        $client->call('ADSModule/SetInstanceSuspended', $data);
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_ChangePackage(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();          
        $provisioningTemplateId = $params['configoption1'];          
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [ 
            'InstanceID' => $service->instanceId,
            'TemplateID' => $provisioningTemplateId,
            'Secret' => 'secretwhmcs'. $params['serviceid'],
        ];
        $client->call('ADSModule/ApplyTemplate', $data);
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}


function AMP_AdminServicesTabFields(array $params)
{
    if($params['status'] == 'Active' || $params['status'] == 'Suspended')
    {
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();
        if(empty($service->instanceId))
        {
            return [
                'Status' => 'Awaiting callback'
            ];
        }
        
        try {
            $client = new Client($params);
            
            $response = $client->call('ADSModule/GetGroup', [ 'GroupId' => $service->targetId ] );
            foreach($response['result']['AvailableInstances'] as $i)
            {
                if($i['InstanceID'] == $service->instanceId)
                {
                    $instance = $i;
                    break;
                }
            }

            $endpoint = $service->endpointUri ? ('<a target="_blank" href="'.$service->endpointUri.'" target="_blank">'.$service->endpoint.'</a>') :  $service->endpoint;
            
            $endpoints = json_decode($service->endpoints, 1);

            foreach($endpoints as $e)
            {
                $vars[$e['DisplayName']] = $e['Uri'] ? ('<a target="_blank" href="'.$e['Uri'].'" target="_blank">'.$e['Endpoint'].'</a>') :  $e['Endpoint'];
            }
            return [
                'State' => $instance['Running'] ? 'Running' : 'Stopped',
                'Instance ID' =>  $service->instanceId,
                'Target ID' =>  $service->targetId,
            ] + $vars;  

        } catch (\Exception $e) {
            return ['Error' => $e->getMessage()];
        }




       
        
    }
    return [];
}

function AMP_ClientArea(array $params)
{
    
    $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();
    $client = new Client($params);

    $action = App::getFromRequest('subaction');
    if (!empty($action)) {
        ob_clean();
        switch ($action) {
            case 'startInstance':
                $result = AMP_startInstance($params);
                $r = ($result == 'success') ? 'success' : 'failure';
                $m = ($result == 'success') ? 'Application has been started successfully' : $result;
                echo json_encode(['result' => $r, 'message' => $m]);   
            break;
            case 'stopInstance':
                $result = AMP_stopInstance($params);
                $r = ($result == 'success') ? 'success' : 'failure';
                $m = ($result == 'success') ? 'Application has been stopped successfully' : $result;
                echo json_encode(['result' => $r, 'message' => $m]);   
            break;
            case 'restartInstance':
                $result = AMP_restartInstance($params);
                $r = ($result == 'success') ? 'success' : 'failure';
                $m = ($result == 'success') ? 'Application has been restarted successfully' : $result;
                echo json_encode(['result' => $r, 'message' => $m]);   
            break;
            case 'resetPassword':
                $result = AMP_resetPassword($params);
                $r = ($result == 'success') ? 'success' : 'failure';
                $m = ($result == 'success') ? 'Application password has ben reseted successfully' : $result;
                echo json_encode(['result' => $r, 'message' => $m]);   
            break;
        }
        die;
    }

    if(!empty($service->instanceId))
    {
        $vars['endpoints'] = json_decode($service->endpoints, 1);

        $vars['appUrl'] = $client->getEndpoint();
        $vars['instanceId'] = $service->instanceId;

        $response = $client->call('ADSModule/GetGroup', [ 'GroupId' => $service->targetId ] );
        foreach($response['result']['AvailableInstances'] as $i)
        {
            if($i['InstanceID'] == $service->instanceId)
            {
                $instance = $i;
                break;
            }
        }

        $vars['state'] = $instance['Running'] ? 'Running' : 'Stopped';

    }
    else
    {
        $vars['errorMessage'] = 'Application provisioning in progress';
    }

    return array(
        'templatefile' => 'templates/overview',
        'vars' => $vars,
    );  
}  


function AMP_AdminCustomButtonArray()
{
    return array(
        "Start Instance" => "startInstance",
        "Stop Instance" => "stopInstance",
        "Restart Instance" => "restartInstance",
        "Reset Password" => "resetPassword",
    );
}

function AMP_startInstance(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }
        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId
        ];
        $client->call('ADSModule/StartInstance', $data);
      

        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_stopInstance(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId
        ];
        $client->call('ADSModule/StopInstance', $data);
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_restartInstance(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $client = new Client($params);
        $data = [
            'InstanceName' => $service->instanceId
        ];
        $client->call('ADSModule/RestartInstance', $data);
        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_resetPassword(array $params)
{
    try {
        AMP_commercialCheck($params);
        $service = Capsule::table('ampServices')->where('serviceId', $params['serviceid'])->first();                    
        if(empty($service->instanceId))
        {
            return 'Instance ID not found';
        }

        $newPassword = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        $client = new Client($params);
        $data = [
            'Username' => $params['username'],
            'NewPassword' => $newPassword
        ];
        $client->call('Core/ResetUserPassword', $data);
        Capsule::table('tblhosting')->updateOrInsert(['id' => $params['serviceid']], ['password' => encrypt($newPassword)]);

        $command = 'SendEmail';
        $postData = array(
            'messagename' => 'AMP Password Reset',
            'id' => $params['serviceid']
        );
        localAPI($command, $postData);

        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AMP_commercialCheck(array $params)
{
    $client = new Client($params);
    try {
        $info = $client->call('Core/GetModuleInfo');
        $featureSet = $info['result']['FeatureSet'];

		$commercial = in_array("CommercialUsage", $featureSet);

        if(!$commercial)
        {
            throw new \Exception( 'AMP has not been configured for commercial usage' );
        }

    }catch (\Exception $e) {
        throw new \Exception($e->getMessage());
    }
}

