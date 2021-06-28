# php-stomp-frame
just stomp frame generate and parse

`stomp` 协议帧，可以用于 tcp、tls、wss等协议

## Cases
`composer require jeeinn/php-stomp-frame`

#### Frame

```php
$userName = 'test';
$password = 'passcode';
$queue = 'service_queue_v1.2';

$stompFrame = new \Stomp\Frame();

# connect frame
$stompFrame->setLogin($userName, $password)->setHeartBeat(0, 10000);
$connectFrame = $stompFrame->getConnect();
print_r($connectFrame);

# subscribe frame
$subscribeFrame = $stompFrame->getSubscribe($queue);
print_r($subscribeFrame);

# send frame
$sendFrame = $stompFrame->setBody('i am tester')->getSend($queue);
```
#### Parser

```php
$message = <<<Message
CONNECTED
server:ActiveMQ/5.11.0.redhat-630495
heart-beat:10000,0
session:ID:broker-amq-2-3-2tx5b-40311-1621354806699-3:162340
version:1.1

Message;

$stompFrame = new \Stomp\Frame();
try {
    $parsed = $stompFrame->parser($message);
    print_r($parsed);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

## Over ws(s)://

base on websocket

`composer require textalk/websocket`

```php
// over wss
require __DIR__ . '/vendor/autoload.php';

$url = 'wss://echo.websocket.org:443';
$userName = 'test';
$password = 'passcode';
$queue = 'service_queue_v1.2';

$stompFrame = new \Stomp\Frame();
# connect frame
$stompFrame->setLogin($userName, $password)->setHeartBeat(0, 10000);
$connectFrame = $stompFrame->getConnect();
# subscribe frame
$subscribeFrame = $stompFrame->getSubscribe($queue);


$client = new \WebSocket\Client($url);
$client->text($connectFrame);
//var_dump($client->isConnected());
$client->text($subscribeFrame);

# loop listening
while (true) {
    try {
        $message = $client->receive();
        $parsed = $stompFrame->parser($message);
        //print_r($parsed);
        # Error, Break while loop to stop listening, Possibly log errors
        if ($parsed['command'] == 'ERROR') {
            echo $parsed['body'];
            $client->close();
            break;
        }
        // Deal your data
        $data = json_decode($parsed['body'], true);
        print_r($data);
        // Act[enter image description here][4] on received message
        // Later, Break while loop to stop listening
    } catch (Exception $e) {
        // Possibly log errors
        echo $e->getMessage();
    }
}
```

## Todo
- [x] CONNECT
- [x] SEND
- [x] SUBSRIBE
- [ ] UNSUBSRIBE
- [ ]  BEGIN
- [ ]  COMMIT
- [ ]  ABORT
- [ ]  ACK
- [ ] NACK
- [ ] DISCONNECT