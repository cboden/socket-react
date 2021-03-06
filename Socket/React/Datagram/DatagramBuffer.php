<?php

namespace Socket\React\Datagram;

use React\EventLoop\LoopInterface;
use Socket\Raw\Socket as RawSocket;

class DatagramBuffer
{
    private $socket;
    private $loop;

    private $outgoing = array();
    private $outgoingLength = 0;

    private $softLimit = 65536;

    public function __construct(RawSocket $socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop   = $loop;
    }

    public function send($data, $remote = null)
    {
        $this->outgoing []= array($data, $remote);
        $this->outgoingLength += strlen($data);

        $this->loop->addWriteStream($this->socket->getResource(), array($this, 'handleWrite'));
        // $this->poller->notify();

        return ($this->outgoingLength < $this->softLimit);
    }

    public function handleWrite()
    {
        list($data, $remote) = array_shift($this->outgoing);
        $this->outgoingLength -= strlen($data);

        if ($remote === null) {
            $this->socket->send($data, 0);
        } else {
            $this->socket->sendTo($data, 0, $remote);
        }

        if (!$this->outgoing) {
            $this->loop->removeWriteStream($this->socket->getResource());
        }
    }

    public function close()
    {
        $this->loop->removeWriteStream($this->socket->getResource());

        $this->outgoing = array();
        $this->outgoingLength = 0;
    }
}
