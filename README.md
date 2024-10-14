# ElectrumPHP
ElectrumPHP is a PHP library designed to interact with the Electrum wallet through RPC calls. 
With this library, you can manage wallets, generate addresses, check balances, make Bitcoin payments and much more using the Electrum daemon.

Update: You can also calculate the ideal estimated fee for your transaction before you make it.

## Requirements

- PHP 8 or higher
- Curl enabled
- putenv enabled
- exec enabled (to start Electrum)
- Electrum installed and configured on your server

## Installation

### The package can be installed using Composer

```bash
  composer require jeankassio/electrumphp
```

## Usage
### Instantiate the ElectrumPHP Class

```php
require_once(dirname(__FILE__) . "/path/to/autoload.php");

use JeanKassio\ElectrumPHP;

$walletPath = dirname(__FILE__) . "/wallet/walletfile";
$walletPass = "0123456789";
$rpcUser = "user";
$rpcPass = "9876543210";
$rpcPort = 7777;
$rpcHost = "127.0.0.1";
$binary = false; //if false, the code find automatically the binary of Electrum

$electrum = new ElectrumPHP($walletPath, $walletPass, $rpcUser, $rpcPass, $rpcPort, $rpcHost, $binary);

```

## Tips

The recommendation is that you start it even if you know that the daemon is already running, as the "start" function checks whether the daemon is running and communicating correctly before trying to execute the code to start the daemon.

## Methods
### Start the Electrum Daemon (if you need)

```php
$electrum->start();
```
### Stop the Electrum Daemon (if you need)

```php
$electrum->stop();
```

### Check if Daemon is Running

```php
if($electrum->isRunning()){
    echo "Electrum daemon is running.";
}
```

### Validate a Bitcoin Address

```php
$address = "1PuJjnF476W3zXfVYmJfGnouzFDAXakkL4";
$valid = $electrum->validate($address);
echo "The Bitcoin address is " . ($valid ? "valid" : "invalid");
```


### Create a New Wallet and receive Seed

```php
$walletPath = dirname(__FILE__) . "/wallet/walletfile";
$seed = false;
$password = "0123456789";
$encrypt = true;
$segwit = true;
$language = "english";
$entropy = 256;
$response = $electrum->createWallet($walletPath, $password, $seed, $encrypt, $segwit, $language, $entropy);
echo "Seed: " . $response['seed'];
```

### Create a New Wallet with your seed

```php
$walletPath = dirname(__FILE__) . "/wallet/walletfile";
$seed = "excess title assist very badge rain pet purchase device narrow awesome recall";
$password = NULL;
$encrypt = false;
$segwit = false;
$response = $electrum->createWallet($walletPath, $password, $seed, $encrypt, $segwit);
echo "Seed: " . $response['seed'];
```

### Create a New Address

```php
$address = $electrum->createAddress();
echo "New address: " . $address;
```

### Get Address Balance

```php
$address = "1PuJjnF476W3zXfVYmJfGnouzFDAXakkL4";
$balance = $electrum->getAddressBalance($address);
echo "Confirmed: " . $balance['confirmed'];
echo "Unconfirmed: " . $balance['unconfirmed'];
```

### Get Wallet Balance

```php
$balance = $electrum->getWalletBalance();
if(isset($balance['confirmed'])){
  echo "Confirmed: " . $balance['confirmed'];
}
				
if(isset($balance['unconfirmed'])){
  echo "Unconfirmed: " . $balance['unconfirmed'];
}
				
if(isset($balance['unmatured'])){
  echo "Unmatured: " . $balance['unmatured'];
}
```

### Get Transaction Details

```php
$txid = "4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b";
$transaction = $electrum->getTransaction($txid);
echo "Transaction details: " . json_encode($transaction);
```

### Pay to a Bitcoin Address

```php
$address = "bc1qlaee57ehqfe2388muudvrf7wvuw2p3lwz0kzh4";
$amount = 0.001;
$fee = 0.00001;
$feerate = NULL;
$fromAddr = "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa";
$fromCoins = NULL;
$change = "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa";
$nocheck = false;
$unsigned = false;
$replaceByFee = true;

$response = $electrum->pay($address, $amount, $fee, $feerate, $fromAddr, $fromCoins, $change, $nocheck, $unsigned, $replaceByFee);
echo "Payment response: " . json_encode($response);
```

### Pay to Multiple Addresses

```php
$outputs = [
  [
    "1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
    0.002
  ],
  [
    "bc1pgmpdnkdyxs6qp2we865tz43umrssvsqweaxvz2cuq5gp9zz6e6tstuj3wa",
    0.0015
  ],
  [
    "bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6taz3gq9cte979624uqjjrv55",
    5.9216
  ]
];
$fee = 0.0009;
$feerate = NULL;
$fromAddr = "bc1plgjpn3cr5khxfee0k40py8njx0qcejrqldldnedqrshvut64jlvs467hnr";
$fromCoins = NULL;
$change = "bc1pqys8mqkneumdyncyz42ljd5zl9gqw4pryd9xsj9gnx5cw6rlvn0sjhdlhy";
$nocheck = false;
$unsigned = false;
$replaceByFee = false;

$response = $electrum->payToMany($outputs, $fee, $feerate, $fromAddr, $fromCoins, $change, $nocheck, $unsigned, $replaceByFee);
echo "Payment response: " . json_encode($response);
```

### Load a Wallet

```php
//Load the wallet instantiated
$electrum->loadWallet();
```

### Get Wallets Currently Open

```php
$wallets = $electrum->getWalletsOpen();
echo "Open wallets: " . json_encode($wallets);
```

### Notify of Address Changes (Webhooks) [Not recommended, could fail]

```php
$address = "bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6taz3gq9cte979624uqjjrv55";
$yourUrl = "https://your-webhook-url";
$response = $electrum->notify($address, $yourUrl);
echo "Notify response: " . json_encode($response);
```

### Delete Address Notification

```php
$address = "bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6taz3gq9cte979624uqjjrv55";
$response = $electrum->deleteNotify($address);
echo "Notification deleted: " . json_encode($response);
```

### Get Private Key of Address in wallet

```php
$address = "bc1pce8yk5epjlqrpnnavelul54jjrv55";
$privateKey = $electrum->getPrivateKeys($address);
echo "Private key: " . $privateKey;
```

### Get Seed of the Wallet

```php
$seed = $electrum->getSeed();
echo "Wallet seed: " . $seed;
```

### If the method you need doesn't exist, make a custom call.

```php
$method = "getaddressunspent";
$params = [
  'address' => "bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6taz3gq9cte979624uqjjrv55"
];
$response = $electrum->custom($method, $params);
echo "Response: " . $response;
```

### Get estimate fee, bests inputs and suggest change address.

```php

$outputs = [
	[
		"bc1qq4khfcyhnds27zqc8wldxu97lzr7u233t8n3e3",
		0.00010604
	],
	[
		"bc1qrdzwvaj84lwlkclp6ruz6y4c6ac7whqltjl6w9",
		0.00010000
	]
];

$checkBalance = true;

$infos = $electrum->getInfosBeforeTransaction($outputs, $checkBalance);
echo json_encode($infos);

```
#### And result are:

```json
{
   "error":false,
   "msg":"Success",
   "data":{
      "inputs":"bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6txxx,bc1pce8yk5epjlqrpnnavelul54ed6663tjqv6taz3",
      "outputs":[
         [
            "bc1qq4khfcyhnds27zqc8wldxu97lzr7u233t8n3e3",
            0.00010604
         ],
         [
            "bc1qrdzwvaj84lwlkclp6ruz6y4c6ac7whqltjl6w9",
            0.0001
         ]
      ],
      "num_inputs":2,
      "num_outputs":2,
      "suggest_change":"bc1qlaee57ehqfe2388muudvrf7wvuw2p3lwz0kzh4",
      "estimated_fee":"0.00000327"
   }
}
```

## Error Handling
### Every RPC call that fails will throw an exception. 
### You can handle these exceptions using simple try-catch blocks:

```php
try{
    $electrum->start();
}catch(Exception $e){
    echo "Error: " . $e->getMessage();
}
```


## Contribution:
Contributions are welcome! If you find a bug or have suggestions for improvements.

Feel free to open an [issue](https://github.com/jeankassio/ElectrumPHP/issues) or submit a [Pull Request](https://github.com/jeankassio/ElectrumPHP/pulls).

## License:
This project is licensed under the [MIT License](https://github.com/jeankassio/ElectrumPHP/blob/main/LICENSE) - see the LICENSE.md file for details.

