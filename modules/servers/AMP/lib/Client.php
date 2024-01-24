<?php

namespace CubeCoders\AMP;

use WHMCS\Database\Capsule;

class Client
{
	private $params;
	public $version;
	public function __construct(array $params)
    {
		$this->version = '2.4.8.0';
		$this->params = new \stdClass;
    $endpoint = (!empty($params['serverhostname']) ? $params['serverhostname'] : $params['serverip']);
    $this->params->serverId = $params['serverid'];
		$this->params->endpoint = ( !empty($params['serversecure']) ? 'https' : 'http').'://'.$endpoint.( !empty($params['serverport'] && $params['serversecure'] != true) ? ':'.$params['serverport'] : '');
		$this->params->username = $params['serverusername'];
		$this->params->password = $params['serverpassword'];

        if(!empty($params['serverid']))
        {
            $session = Capsule::table('ampSessions')->where('serverId', $params['serverid'])->value('sessionId');
		}
		$this->params->sessionId = !empty($session) ? $session : $this->getSessionId();
	}

	public function getEndpoint()
	{
		return $this->params->endpoint;
	}

	private function getSessionId()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->params->endpoint . '/API/Core/Login');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$data = [
			'username' => $this->params->username,
			'password' => $this->params->password,
			'token' => '',
			'rememberMe' => false
		];
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$headers = [
			"Accept: application/vnd.cubecoders-ampapi",
			"User-Agent: CCL/AMPAPI-NodeJS",
			'Content-type: application/json',
			'Content-length:'.strlen(json_encode($data))
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
		$decoded = json_decode($response, 1);

        if(empty($decoded))
		{
			throw new \Exception('Unknown response');
        }

		if(!empty($decoded['Error']))
		{
			throw new \Exception($decoded['Message']);
        }


		if(!empty($decoded['Reason']) && !$decoded['Status'])
		{
			throw new \Exception($decoded['Reason']);
		}

        curl_close($ch);

        if(!empty($this->params->serverId) && !empty($decoded['sessionID']))
        {
            Capsule::table('ampSessions')->updateOrInsert(['serverId' => $this->params->serverId], ['sessionId' => $decoded['sessionID']]);
        }

		return $decoded['sessionID'];
	}

	public function call(string $query, array $data = [])
	{
		$data['SESSIONID'] = $this->params->sessionId;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->params->endpoint . '/API/'. $query);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (!empty($data)) {
			$post = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		$headers = [
			"Accept: application/vnd.cubecoders-ampapi",
			"User-Agent: CCL/AMPAPI-NodeJS",
			'Content-type: application/json',
			'Content-length:'.strlen($post)
		];

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch);
		logModuleCall("AMP", $query, $post, $response);
		$decoded = json_decode($response, 1);

		if($decoded['Title'] == 'Unauthorized Access' || $decoded['Status'] == false)
		{
			$data['SESSIONID'] = $this->getSessionId();
			$post = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$headers[3] = 'Content-length:'.strlen($post);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$response = curl_exec($ch);
			$decoded = json_decode($response, 1);
		}
		curl_close($ch);

        if(empty($decoded))
		{
			throw new \Exception('Unknown response');
        }

		if(!empty($decoded['Error']))
		{
			throw new \Exception($decoded['Message']);
		}

		if(!empty($decoded['Reason']) && !$decoded['Status'])
		{
			throw new \Exception($decoded['Reason']);
		}

		return $decoded;
	}
}
