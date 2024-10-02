<?php
namespace JeanKassio;

class ElectrumPHP{
	
    private $url;
    private $user;
    private $pass;
	private $port;
	private $wallet;
	private $walletpass;
	
    public function __construct($walletPath, $walletPass, $user, $pass, $port, $host = '127.0.0.1'){
		
        $this->user = $user;
        $this->pass = $pass;
        $this->port = $port;
        $this->wallet = $walletPath;
        $this->walletpass = $walletPass;
        $this->url = "http://$host:$port";
		
    }
	
	/*
	 *	$rpcuser:	User RPC;
	 *	$rpcpass:	Password RPC;
	 *	$rpcport:	Port Used By RPC;
	 */
	public function start($rpcuser = null, $rpcpass = null, $rpcport = null){
		
		if(!$this->isRunning()){
			
			$params = "-d --rpcuser '{$this->user}' --rpcpassword '{$this->pass}' --rpcport {$this->port}";
			
			$output = shell_exec("electrum daemon {$params}");
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
		
		if($this->isRunning()){
			$output = shell_exec("electrum stop");
			if(str_contains($output, 'Daemon stopped')){
				return true;
			}else{
				throw new \Exception('Could not stop Electrum daemon.');
			}
		}else{
			return true;
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
		
		$params = [$this->wallet];
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
			$params = [$seed, $password, $encrypt, ($segwit ? "segwit" : "standard"), $walletPath];
			
			$response = $this->call("create", $params);
			
			if(str_contains($response, 'path')){
				$this->wallet = $walletPath;
				$this->walletpass = $password;
				return json_decode($response, true);
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
		
		$params = [$entropy, $language, ($segwit ? "segwit" : "standard")];
		return $this->call("make_seed", $params);
		
	}
	
	public function createAddress(){
		
		$params = [$this->wallet];
		return $this->call("createnewaddress", $params);
		
	}
	
	/*
	 *	$address:	A valid Bitcoin Address;
	 */
	public function getAddressBalance($address){
		
		$params = [$address];
		return $this->call("getaddressbalance", $params);
		
	}
	
	/*
	 *	$address:	A valid Bitcoin Address;
	 */
	public function getAddressHistory($address){
		
		$params = [$address];
		return $this->call("getaddresshistory", $params);
		
	}
	
	public function getWalletBalance(){
		
		$params = [$this->wallet];
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
		
		$params = [$address, $url];
		return $this->call("notify", $params);
		
	}
	
	/*
	 *	$address: 	A valid Bitcoin address;
	 */
	public function deleteNotify($address){
		
		$params = [$address];
		return $this->call("notify", $params);
		
	}
	
	/*
	 *	$address: 	A valid Bitcoin address;
	 */
	public function getPrivateKeys($address){
		
		$params = [$address, $this->walletpass, $this->wallet];
		return $this->call("getprivatekeys", $params);
		
	}
	
	public function getSeed(){
		
		$params = [$this->walletpass, $this->wallet];
		return $this->call("getseed", $params);
		
	}
	
	/*
	 *	$privateKey:	Bitcoin Private Key;
	 */
	public function importPrivKey($privateKey){
		
		$params = [$privateKey, $this->walletpass, $this->wallet];
		return $this->call("importprivkey", $params);
		
	}
	
	/*
	 *	$txid:	Your TXID;
	 */
	public function getTransaction($txid){
		
		$params = [$txid, $this->wallet];
		return $this->call("gettransaction", $params);
		
	}
	
	public function checkSyncronization(){
		
		$params = [$this->wallet];
		return $this->call("is_synchronized", $params);
		
	}
	
	public function getWalletsOpen(){
		
		$params = [];
		return $this->call("list_wallets", $params);
		
	}
	
	public function getAddressesWallet(){
		
		$params = [false, false, false, false, false, false, false, $this->wallet];
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
		
		$params = [$address, $amount, $fee, $feerate, $fromAddr, $fromCoins, $change, $nocheck, $unsigned, $replaceByFee, $this->walletpass, NULL, true, $this->wallet];
		return $this->call("payto", $params);
		
	}
	
	/*
	 *	$addresses:	Addresses and values to send -> [["addr", 0.001],["addr", 0.2]]
	 *	$fee:		Fee in BTC to send;
	 *	$feerate:	Feerate to send Bitcoins;
	 *	$fromAddr:	Choose one address to get the Bitcoins;
	 *	$fromCoins:	Type of Coin to send (Null default);
	 *	$change:	Address to send change;
	 *	$nocheck:	No check transaction;
	 *	$unsigned:	Unsigned val;
	 *	$replaceByFee:	Activate the RBF mode in transaction;
	 */
	public function payToMany($addresses, $fee = null, $feerate = null, $fromAddr = null, $fromCoins = null, $change = null, $nocheck = false, $unsigned = false, $replaceByFee = true){
		
		$params = [$addresses, $fee, $feerate, $fromAddr, $fromCoins, $change, $nocheck, $unsigned, $replaceByFee, $this->walletpass, NULL, true, $this->wallet];
		return $this->call("payto", $params);
		
	}
	
	/*
     *    $walletPath:	Wallet path;
	 *	$password: 		Wallet password;
	 */
	public function loadWallet($walletPath, $password = NULL){
		
		$params = [$walletPath, $password];
        $response = $this->call('load_wallet', $params);
		
		if(!str_contains($response, 'Traceback')){
			$this->wallet = $walletPath;
			$this->walletpass = $password;
			return true;
		}else{
			return false;
		}
		
    }
	
    public function isRunning(){
        
		try{
			
			$status = shell_exec("electrum getinfo");
		
			if(str_contains($status, 'Daemon not running')){
				return false;
			}else{
				
				$result = json_decode($status, true);
				
				return (isset($result['connected']) && $result['connected']);
				
			}
			
		}catch(Throwable $e){
			return false;
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
