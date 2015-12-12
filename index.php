<?php

require('TooBasic/init.php');
require('JsonRpcClient.php');

class BitcoinInsight_Api extends TooBasic_Controller
{
	protected $_bt;

	protected function _construct()
	{
		header('Content-Type: application/javascript');
		require('config.php');
		$this->_bt = new JsonRpcClient($bitcoinRpc);
	}

	public function getVersion()
	{
		print json_encode(['version' => '0.3.1']);
	}

	public function getPeer()
	{
		print json_encode([
			'connected' => true,
			'host' => '127.0.0.1',
			'port' => null,
		]);
	}

	public function getCurrency()
	{
		$ch = curl_init('https://blockchain.info/ticker');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);

		$response = curl_exec($ch);

		if (false !== $response)
		{
			$response = json_decode($response);
			$response = $response->USD->last;
		}

		print json_encode([
			'status' => 200,
			'data' => [
				'blockchain.info' => $response,
			]
		]);
	}

	public function getSync()
	{
		$i = $this->_bt->getinfo();

		print json_encode([
			'status' => 'finished',
			'blockChainHeight' => $i->blocks,
			'syncPercentage' => 100,
			'height' => $i->blocks,
			'error' => null,
			'type' => 'bitcoinXt',
		]);
	}

	public function getBlocks()
	{
		$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;
#		$date = $_GET['blockDate'];
		$hash = $this->_bt->getbestblockhash();

		$blocks = [];
		for ($i=0; $i < $limit; $i++)
		{
			$block = $this->_bt->getblock($hash);
			$block->txlength = count($block->tx); unset($block->tx);
			$block->poolinfo = [];

			array_push($blocks, $block);
			$hash = $block->previousblockhash;
		}

		print json_encode([
			'blocks' => $blocks,
			'length' => $limit,
			'pagination' => [
				'next' => '2015-12-10', 'prev' => '2015-12-08', 'currentTs' => 1449705599, 'current' => '2015-12-09', 'isToday' => true, 'more'=>true, 'moreTs'=> 1449705599,
			],
		]);
	}

	public function getStatus()
	{
		switch ($_GET['q'])
		{
			default:
				$i = $this->_bt->getinfo();

				print json_encode([
					'info' => $i
				]);
			break;

			case 'getLastBlockHash':
				$h = $this->_bt->getbestblockhash();

				print json_encode([
					'syncTipHash' => $h,
					'lastblockhash' => $h,
				]);
			break;
		}
	}

	public function getBlock($hash)
	{
		$b = $this->_bt->getblock($hash);

		if ($b->height <  210000)
			$b->reward = 50;
		elseif ($b->height <  420000)
			$b->reward = 25;
		elseif ($b->height <  630000)
			$b->reward = 12.5;
		else
			$b->reward = 6.25;

		$b->isMainChain = true;
		$b->poolInfo = [];

		print json_encode($b);
	}

	public function getTxs()
	{
		$b = $this->_bt->getblock($_GET['block']);
		$limit = 25;

#		$_GET['block'];
#		$_GET['pageNum'];

		$txs = [];
		foreach (array_slice($b->tx, 0, $limit) as $txHash)
			array_push($txs, $this->_getTx($txHash));

		print json_encode([
			'pagesTotal' => ceil(count($b->tx)/$limit),
			'txs' => $txs,
		]);
	}

	public function getTx($txHash)
	{
		print json_encode($this->_getTx($txHash));
	}

	protected function _getTx($hash)
	{
		$tx = $this->_bt->getrawtransaction($hash, 1);
		$tx->size = (4+1+4+1);
		$tx->valueIn = 0;
		$tx->valueOut = 0;

		// only first tx will contain coinbase
		if (empty($txs) && isset($tx->vin[0]->coinbase))
			$tx->isCoinBase = true;

		foreach ($tx->vin as $vin)
		{
			$tx->size += (32+4+1+4);

			if (isset($vin->coinbase))
				continue;

			$tx->size += strlen($vin->scriptSig->hex);

			$txIn = $this->_bt->getrawtransaction($vin->txid, 1);
			$vin->value = $txIn->vout[ $vin->vout ]->value;
			$vin->valueSat = 100*1000*1000*$vin->value;
			$vin->addr = $txIn->vout[ $vin->vout ]->scriptPubKey->addresses[0]; #?
			$vin->doubleSpentTxID = null;

			$tx->valueIn += $vin->value;
		}

		foreach ($tx->vout as $vout)
		{
			$tx->size += (32+4+1+4)+strlen($vout->scriptPubKey->hex);
			$tx->valueOut += $vout->value;
		}

		if (!isset($tx->isCoinBase))
			$tx->fees = $tx->valueIn - $tx->valueOut;

		return $tx;
	}

	// 'index' is the default action
	public function getIndex()
	{
		// Until we have some sort of documentation
		header('Location: /');
	}

	public function get($action)
	{
		throw new Exception('Sorry we have never heard of this "'. $action .'" you speak of');
	}

	// This action gets called when an error occurs; eg the action is unknown
	protected function _handle(Exception $e)
	{
		if (!headers_sent())
			http_response_code(500);

		print json_encode(['error' => $e]);
	}
}

BitcoinInsight_Api::dispatch(substr($_SERVER['REQUEST_URI'], strlen('/api')));