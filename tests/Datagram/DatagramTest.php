<?php

use Socket\React\Datagram\Datagram;
use Socket\React\Datagram\Factory;

class DatagramDatagramTest extends TestCase
{
    /**
     * @var Socket\Raw\Factory
     * @type Factory
     */
    protected $factory;

    protected $loop;

    public function setUp()
    {
        $this->loop = React\EventLoop\Factory::create();
        $this->factory = new Factory($this->loop);
    }

    public function testClientServerUdp4()
    {
        $loop = $this->loop;
        $this->factory->createServer('127.0.0.1:1337')->then(function (Datagram $socket) use ($loop) {
            $socket->on('message', function($message, $remote, Datagram $socket) use ($loop) {
                // for every message we receive, send back the reversed message (ABC -> CBA)
                $socket->send(strrev($message), $remote);

                $loop->addTimer(0.1, function() use ($socket) {
                    $socket->close();
                });
            });
        });

        $that = $this;
        $once = $this->expectCallableOnce();
        $this->factory->createClient('127.0.0.1:1337')->then(function (Datagram $socket) use ($that, $once) {
            $socket->send('test');

            $socket->on('message', $once);
            $socket->on('message', function ($message, $remote, $socket) use ($that) {
                $that->assertEquals('tset', $message);
                $that->assertEquals('127.0.0.1:1337', $remote);

                $socket->close();
            });
        });

        $this->loop->run();
    }

    /** "connecting" and sending message to an unbound port should not raise any errors (messages will be discarded) */
    public function testClientUdp4Unbound()
    {
        $this->factory->createClient('127.0.0.1:2')->then(function (Datagram $socket) {
            $socket->send('test');
        });

        $this->loop->tick();
        $this->loop->tick();
    }

    /** test to make sure the loop ends once the last socket has been closed */
    public function testClientCloseEndsLoop()
    {
        $this->factory->createClient('127.0.0.1:2')->then(function (Datagram $socket) {
            $socket->close();
        });

        $this->loop->run();
    }
}
