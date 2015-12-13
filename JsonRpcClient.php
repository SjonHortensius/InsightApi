<?php
class JsonRpcClient
{
	protected $_url;
	protected $_id = 1;
	protected $_isNotification = false;
	protected $_ch;

	public function __construct($url)
	{
		$this->_url = $url;

		$this->_ch = curl_init($this->_url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, ['Content-type' => 'application/json']);
		curl_setopt($this->_ch, CURLOPT_POST, true);
	}

	public function setNotification($n = true)
	{
		$this->_isNotification = (bool)$n;
	}

	public function __call($method, $params)
	{
		$requestId = $this->_isNotification ? null : $this->_id;
		$request = json_encode([
			'method' => $method,
			'params' => array_values($params),
			'id' => $requestId,
		]);

		if (!$this->_ch)
			$this->_ch = curl_init($this->_url);

		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $request);

		$response = curl_exec($this->_ch);
		if (false == $response)
			throw new Exception('Curl error: '. curl_error($this->_ch));

		if ($this->_isNotification)
			return true;

		$response = json_decode($response);

		if ($response->id != $requestId)
			throw new Exception('Unexpected responseId '. $response->id .', expected '. $requestId);

		if (!is_null($response->error))
			throw new Exception('Request error: '. $response->error->message);

		return $response->result;
	}

	public function __destruct()
	{
		curl_close($this->_ch);
	}
}