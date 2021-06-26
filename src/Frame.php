<?php
namespace Stomp;

use Exception;

class Frame
{
    const BYTE = ['LF' => "\n", 'NULL' => "\x00\n"];
    const COMMAND_CONNECT = 'CONNECT';
    const COMMAND_SUBSCRIBE = 'SUBSCRIBE';
    const COMMAND_SEND = 'SEND';
    const VERSIONS = '1.0,1.1,1.2';
    const STOMP_SERVER_COMMAND = ['CONNECTED', 'ERROR', 'MESSAGE', 'RECEIPT'];

    protected $command;
    protected $headers = [
        'accept-version' => self::VERSIONS,
        'heart-beat' => '0,0',
    ];
    protected $body;

    public function __construct($event = null, $body = null)
    {
        $this->body = $body;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers ?: $this->headers;
    }

    /**
     * @param array $header
     * @return $this
     */
    public function addHeaders($header)
    {
        $this->headers = array_merge($this->headers, $header);
        return $this;
    }

    /**
     * @param string $login
     * @param $passcode
     * @return $this
     */
    public function setLogin($login = '', $passcode = '')
    {
        $this->addHeaders([
            'login' => $login,
            'passcode' => $passcode,
        ]);
        return $this;
    }

    /**
     * @param int $send
     * @param int $receive
     * @return $this
     */
    public function setHeartBeat($send = 0, $receive = 0)
    {
        $this->addHeaders(['heart-beat' => $send . ',' . $receive,]);
        return $this;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body = '')
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnect()
    {
        $this->command = self::COMMAND_CONNECT;
        return $this->getFrame();
    }

    /**
     * @param string $destination
     * @param int $id
     * @param string $ackType
     * @return string
     */
    public function getSubscribe($destination = '', $id = 0, $ackType = 'client')
    {
        $this->command = self::COMMAND_SUBSCRIBE;
        $this->headers = [
            'id' => $id,
            'ack' => $ackType,
            'destination' => $destination,
        ];
        return $this->getFrame();
    }

    public function getSend($destination, $body = '', $contentType = 'text/plain')
    {
        $this->command = self::COMMAND_SEND;
        $this->headers = [
            'destination' => $destination,
        ];
        $contentType && $this->addHeaders(['content-type' => $contentType]);
        $body && $this->body = $body;
        return $this->getFrame();
    }

    /**
     * @return string
     */
    final private function getFrame()
    {
        $lines = new \ArrayObject();
        $lines->append($this->command . self::BYTE['LF']);

        # add header
        foreach ($this->headers as $k => $v) {
            $lines->append($k . ':' . $v . self::BYTE['LF']);
        }

        # add message, if any
        $lines->append(self::BYTE['LF']);
        if (!is_null($this->body)) {
            $lines->append($this->body);
        }
        # terminate with null octet
        $lines->append(self::BYTE['NULL']);

        return implode('', $lines->getArrayCopy());
    }

    /**
     * @param $message
     * @return array
     * @throws Exception
     */
    public function parser($message)
    {
        if (empty($message) || in_array($message, self::BYTE)) throw new Exception('StompFrameParser: message is empty!');

        $lines = explode(self::BYTE['LF'], trim($message));
        // print_r($lines);
        $command = $lines[0];
        if (!in_array($command, self::STOMP_SERVER_COMMAND)) throw new Exception("StompFrameParser: server command illegal\n$message");
        $headers = [];
        # get all header, get key, value from raw header
        $i = 1;
        while (isset($lines[$i]) && $lines[$i]) {
            $header = explode(':', $lines[$i]);
            if ($header > 2) {
                $k = array_shift($header);
                $v = implode(':', $header);
            } else {
                list($k, $v) = $header;
            }
            $headers[$k] = $v;
            $i++;
        }
        $i++;
        $body = null;
        if (isset($lines[$i])) {
            $body = rtrim($lines[$i], self::BYTE['NULL']);
            if (self::BYTE['NULL'] == $lines[$i]) $body = null;
        }

        return ['command' => $command, 'headers' => $headers, 'body' => $body];
    }
}
