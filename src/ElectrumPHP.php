<?php
namespace JeanKassio;

class ElectrumPHP{
	
    private $binary;
    private $url;
    private $user;
    private $pass;
	private $port;
	private $wallet;
	private $walletpass;
	
    public function __construct($walletPath, $walletPass, $user, $pass, $port, $host = '127.0.0.1', $binary = false){
		
		putenv("PATH=/home/www/.local/bin:/usr/local/bin:/usr/bin:/bin");
		
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->wallet = $walletPath;
        $this->walletpass = $walletPass;
        $this->url = "http://$host:$port";
		$this->binary = (!$binary ? $this->getBinary() : $binary);
		
    }
	
	/*
	 *	$rpcuser:	User RPC;
	 *	$rpcpass:	Password RPC;
	 *	$rpcport:	Port Used By RPC;
	 */
	public function start($repeat = true){
		
		if(!$this->isRunning()){
			
			$params = "-d --rpcuser '{$this->user}' --rpcpassword '{$this->pass}' --rpcport {$this->port}";
			
			$output = NULL;
			$code = null;
			
			exec("{$this->binary} daemon {$params} 2>&1", $output, $code);
			
			if(is_null($output)){
				return false;
			}
			
			$output = implode(" ", $output);
			
			if(str_contains($output, 'already running') && $repeat){
				$this->stop();
				sleep(1);
				return $this->start(false);
			}
			
			if(str_contains($output, 'starting daemon')){
				return true;
			}else{
				throw new \Exception('Could not start Electrum daemon.');
			}
			
		}else{
			return true;
		}
		
    }
	
    public function stop(){
		
		$output = NULL;
		$code = null;
		
		exec("{$this->binary} stop 2>&1", $output, $code);
		
		if(is_null($output)){
			return false;
		}
		
		$output = implode(" ", $output);
		
		if(str_contains($output, 'Daemon stopped')){
			return true;
		}
			
		$params = [];
		$response = $this->call("stop", $params);
		
		if(!is_array($response) && (str_contains($response, 'Daemon stopped'))){
			return true;
		}else{
			throw new \Exception('Could not stop Electrum daemon.');
		}
		
    }
	
	/*
	 *	$method:	Method used;
	 *	$params:	Method params;
	 */
	public function custom($method, $params){
		
		return $this->call($method, $params);
		
	}
	
	public function closeWallet(){
		
		$params = [
			'wallet_path' => $this->wallet
		];
		return $this->call("close_wallet", $params);
		
	}
	
	/*
	 *	$walletPath:	path of wallet to create;
	 *	$setSeed: 	string of seed choose by you, if false Electrum create for you and return this;
	 *	$password:	Password of wallet;
	 *	$segwit:	Boolean, if the wallet is Segwit;
	 *	$encrypt:	Boolean, if the wallet will be encrypted;
	 *	$language:	String, language of Seed to automatically create;
	 *	$entropy:	Entropy of Seed to automatically create;
	 */
	public function createWallet($walletPath, $password = null, $setSeed = false, $encrypt = true, $segwit = true, $language = "english", $entropy = 256){
		
		try{
			
			$seed = ($setSeed ?? $this->makeSeed($segwit, $language, $entropy));
			$params = [
				'passphrase' => $seed, 
				'password' => $password, 
				'encrypt_file' => $encrypt, 
				'seed_type' => ($segwit ? "segwit" : "standard"), 
				'wallet_path' => $walletPath
			];
			
			$response = $this->call("create", $params);
			
			if(is_array($response) && isset($response['path'])){
				
				$this->wallet = $walletPath;
				$this->walletpass = $password;
				
				return $response;
				
			}else{
				return false;
			}
			
		}catch(Throwable $e){
			return false;
		}
		
	}
	
	/*
	 *	$segwit:	if the wallet is segwit;
	 *	$language:	String, language of Seed to automatically create;
	 *	$entropy:	Entropy of Seed to automatically create;
	 */
	public function makeSeed($segwit = true, $language = "english", $entropy = 256){
		
		$params = [
			'nbits' => $entropy, 
			'language' => $language, 
			'seed_type' => ($segwit ? "segwit" : "standard")
		];
		return $this->call("make_seed", $params);
		
	}
	
	public function createAddress(){
		
		$params = [
			'wallet' => $this->wallet
		];
		return $this->call("createnewaddress", $params);
		
	}
	
	/*
	 *	$address:	A valid Bitcoin Address;
	 */
	public function getAddressBalance($address){
		
		$params = [
			'address' => $address
		];
		return $this->call("getaddressbalance", $params);
		
	}
	
	/*
	 *	$address:	A valid Bitcoin Address;
	 */
	public function getAddressHistory($address){
		
		$params = [
			'address' => $address
		];
		return $this->call("getaddresshistory", $params);
		
	}
	
	/*
	 *	$showAddresses:	Boolean, if show address in transactions;
	 */
	public function getWalletHistory($showAddresses, $fromHeight = NULL){
		
		$params = [
			'show_addresses' => $showAddresses,
			'wallet' => $this->wallet,
			'from_height' => $fromHeight
		];
		return $this->call("onchain_history", $params);
		
	}
	
	public function getWalletBalance(){
		
		$params = [
			'wallet' => $this->wallet
		];
		return $this->call("getbalance", $params);
		
	}
	
	public function getFeeRate(){
		
		$params = [];
		return $this->call("getfeerate", $params);
		
	}
	
	/*
	 *	$address: 	A valid Bitcoin address;
	 *	$url:		A url to receive webhook of Electrum;
	 */
	public function notify($address, $url){
		
		$params = [
			'address' => $address, 
			'URL' => $url
		];
		return $this->call("notify", $params);
		
	}
	
	/*
	 *	$address: 	A valid Bitcoin address;
	 */
	public function deleteNotify($address){
		
		$params = [
			'address' => $address
		];
		return $this->call("notify", $params);
		
	}
	
	/*
	 *	$address: 	A valid Bitcoin address;
	 */
	public function getPrivateKeys($address){
		
		$params = [
			'address' => $address, 
			'password' => $this->walletpass, 
			'wallet' => $this->wallet
		];
		return $this->call("getprivatekeys", $params);
		
	}
	
	public function getSeed(){
		
		$params = [
			'password' => $this->walletpass, 
			'wallet' => $this->wallet
		];
		return $this->call("getseed", $params);
		
	}
	
	/*
	 *	$addr:	Bitcoin Address;
	 */
	public function validate($addr){
		
		$params = [
			'address' => $addr
		];
		return $this->call("validateaddress", $params);
		
	}
	
	/*
	 *	$privateKey:	Bitcoin Private Key;
	 */
	public function importPrivKey($privateKey){
		
		$params = [
			'privkey' => $privateKey, 
			'password' => $this->walletpass, 
			'wallet' => $this->wallet
		];
		return $this->call("importprivkey", $params);
		
	}
	
	/*
	 *	$txid:	Your TXID;
	 */
	public function getTransaction($txid){
		
		$params = [
			'txid' => $txid, 
			'wallet' => $this->wallet
		];
		$raw = $this->call("gettransaction", $params);
		return $this->deserialize($raw);
		
	}
	
	/*
	 *	$txid:	Your TXID;
	 */
	public function getConfirmations($txid){
		
		$params = [
			'txid' => $txid, 
			'wallet' => $this->wallet
		];
		$tr = $this->call("get_tx_status", $params);
		return ($tr['confirmations'] ?? false);
		
	}
	
	public function checkSyncronization(){
		
		$params = [
			'wallet' => $this->wallet
		];
		return $this->call("is_synchronized", $params);
		
	}
	
	public function getWalletsOpen(){
		
		$params = [];
		return $this->call("list_wallets", $params);
		
	}
	
	public function getAddressesWallet($balance = false, $receiving = false, $change = false, $labels = false, $frozen = false, $unused = false, $funded = false){
		
		$params = [
			'receiving' => $receiving, 
			'change' => $change, 
			'labels' => $labels, 
			'frozen' => $frozen, 
			'unused' => $unused, 
			'funded' => $funded, 
			'balance' => $balance, 
			'wallet' => $this->wallet
		];
		return $this->call("listaddresses", $params);
		
	}
	
	/*
	 *	$address:	Send Bitcoins to this address;
	 *	$amount:	Amount in BTC to send;
	 *	$fee:		Fee in BTC to send;
	 *	$feerate:	Feerate to send Bitcoins;
	 *	$fromAddr:	Choose one address to get the Bitcoins;
	 *	$fromCoins:	Type of Coin to send (Null default);
	 *	$change:	Address to send change;
	 *	$nocheck:	No check transaction;
	 *	$unsigned:	Unsigned val;
	 *	$replaceByFee:	Activate the RBF mode in transaction;
	 */
	public function pay($address, $amount, $fee = null, $feerate = null, $fromAddr = null, $fromCoins = null, $change = null, $nocheck = false, $unsigned = false, $replaceByFee = true){
		
		$params = [
			'destination' => $address, 
			'amount' => $amount, 
			'fee' => $fee, 
			'feerate' => $feerate, 
			'from_addr' => $fromAddr, 
			'from_coins' => $fromCoins, 
			'change_addr' => $change, 
			'nocheck' => $nocheck, 
			'unsigned' => $unsigned, 
			'rbf' => $replaceByFee, 
			'password' => $this->walletpass, 
			'locktime' => NULL, 
			'addtransaction' => true, 
			'wallet' => $this->wallet
		];
		$raw = $this->call("payto", $params);
		return $this->call("broadcast", [$raw]);
		
	}
	
	/*
	 *	$outputs:	Addresses and values to send -> [["addr", 0.001],["addr", 0.2]]
	 *	$fee:		Fee in BTC to send;
	 *	$feerate:	Feerate to send Bitcoins;
	 *	$fromAddr:	Choose one address to get the Bitcoins;
	 *	$fromCoins:	Type of Coin to send (Null default);
	 *	$change:	Address to send change;
	 *	$nocheck:	No check transaction;
	 *	$unsigned:	Unsigned val;
	 *	$replaceByFee:	Activate the RBF mode in transaction;
	 */
	public function payToMany($outputs, $fee = null, $feerate = null, $fromAddr = null, $fromCoins = null, $change = null, $nocheck = false, $unsigned = false, $replaceByFee = true){
		
		$params = [
			'outputs' => $outputs, 
			'fee' => $fee, 
			'feerate' => $feerate, 
			'from_addr' => $fromAddr, 
			'from_coins' => $fromCoins, 
			'change_addr' => $change, 
			'nocheck' => $nocheck, 
			'unsigned' => $unsigned, 
			'rbf' => $replaceByFee, 
			'password' => $this->walletpass, 
			'locktime' => NULL, 
			'addtransaction' => true, 
			'wallet' => $this->wallet
		];
		$raw = $this->call("paytomany", $params);
		return $this->call("broadcast", [$raw]);
		
	}
	
	public function loadWallet(){
		
		$params = [
			'wallet_path' => $this->wallet, 
			'password' => $this->walletpass
		];
        $response = $this->call('load_wallet', $params);
		
		if(is_array($response)){
			$response = implode(" ", $response);
		}
		
		return (is_null($response));
		
    }
	
    public function isRunning(){
        
		try{
			
			$params = [];
			$response = $this->call("getinfo", $params);
			
			if(is_null($response) || empty($response) || str_contains(implode(" ", $response), 'Daemon not running') || str_contains(implode(" ", $response), 'Connection refused')){
				return false;
			}else{
				return (isset($response['connected']) && $response['connected']);
			}
			
		}catch(\Exception $e){
			return false;
		}catch(Throwable $e){
			return false;
		}
		
    }
	
	public function getInfosBeforeTransaction($outputs, $checkBalance){
		
		$estimated_fee = 0;
		$total_outputs = 0;
		$selected_balance = 0;
		$num_inputs = 0;
		$num_outputs = count($outputs);
		$inputs = [];
		$change_address = '';
		
		$type_input_counts = ['P2PKH' => 0, 'P2SH' => 0, 'P2WPKH' => 0];
		
		foreach($outputs as $output){
			$total_outputs += $output[1];
		}
		
		$feerate = (($sbit = $this->getFeeRate()) !== false ? $sbit["sat/kvB"] : 10000);
		
		$addresses_with_balances = $this->getAddressesWallet(true);
		
		$filtered_addresses = [];
		
		foreach ($addresses_with_balances as $address_info){
			
			$address = $address_info[0];
			$balance = floatval($address_info[1]);
			
			if($balance > 0){
				$filtered_addresses[] = ['address' => $address, 'balance' => $balance];
			}
			
		}
		
		usort($filtered_addresses, function($a, $b) {
			return $b['balance'] - $a['balance'];
		});
		
		foreach($filtered_addresses as $address_info){
			
			$address = $address_info['address'];
			$balance = $address_info['balance'];
			
			$inputs[] = $address;
			$selected_balance += $balance;
			$num_inputs++;
			
			$type = $this->detectAddressType($address);
			
			if(isset($type_input_counts[$type])){
				$type_input_counts[$type]++;
			}
			
			$estimated_fee = $this->calculateFee($type_input_counts, $num_outputs, $feerate);
			
			if($selected_balance >= $total_outputs + $estimated_fee){
				break;
			}
			
		}
		
		if($selected_balance > ($total_outputs + $estimated_fee)){
			
			foreach($filtered_addresses as $address_info){
				
				if(!in_array($address_info['address'], $inputs)){
					
					$change_address = $address_info['address'];
					break;
					
				}
				
			}
			
		}elseif($checkBalance){
			
			return [
				'error' => true,
				'msg' => "Insufficient balance",
				'data' => []
			];
			
		}
		
		$inputs_string = implode(',', $inputs);
		
		return [
			'error' => false,
			'msg' => "Success",
			'data' => [
				'inputs' => $inputs_string,
				'outputs' => $outputs,
				'num_inputs' => $num_inputs,
				'num_outputs' => $num_outputs,
				'suggest_change' => $change_address,
				'estimated_fee' => $estimated_fee
			]
		];
		
	}
	
	private function deserialize($raw){
		
		$params = [
			'tx' => $raw
		];
		return $this->call("deserialize", $params);
		
	}
	
	private function detectAddressType($address){
		
		if(strpos($address, '1') === 0){
			return 'P2PKH';
		}elseif (strpos($address, '3') === 0){
			return 'P2SH';
		}elseif (strpos($address, 'bc1') === 0){
			return 'P2WPKH';
		}
		
		return 'Unknown';
		
	}

	private function calculateFee($type_input_counts, $num_outputs, $feerate){
		
		$size_per_input = [
			'P2PKH' => 148,
			'P2SH' => 91,
			'P2WPKH' => 68
		];
		
		$size = 0;
		foreach ($type_input_counts as $type => $count){
			if(isset($size_per_input[$type])){
				$size += $size_per_input[$type] * $count;
			}
		}
		
		$size += ($num_outputs * 34) + 10;
		
		return number_format((($size / 1000) * $feerate) / 100000000, 8, ".", "");
		
	}

	
	private function getBinary(){
		
		$output = NULL;
		$code = null;
		
		exec("electrum --help 2>&1", $output, $code);
		
		if(!is_null($output)){
			
			$output = implode(" ", $output);
		
			if(str_contains($output, 'usage: electrum')){
				return "electrum";
			}
			
		}
		
		exec("which electrum", $output);
		
		if(is_array($output) && count($output) > 0){
			
			return $output[0];
			
		}else{
			throw new \Exception('Unable to locate Electrum binary.');
		}
		
	}
	
    private function call($method, $params = []){
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => time()
        ]);
		
		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("{$this->user}:{$this->pass}")
        ]);

        $response = curl_exec($ch);
		
			
			
        if(curl_errno($ch)){
			throw new \Exception('Curl error: ' . curl_error($ch));
        }
		
        curl_close($ch);

        $decodedResponse = json_decode($response, true);

        if(isset($decodedResponse['error'])){
            throw new \Exception('RPC Error: ' . json_encode($decodedResponse['error']));
        }

        return ($decodedResponse['result'] ?? null);
		
    }
	
}
