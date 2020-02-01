<?php
declare (strict_types = 1);

use Hprose\RPC\Core\Client;
use Hprose\RPC\Core\MockServer;
use Hprose\RPC\Core\Service;
use Hprose\RPC\Plugins\ExecuteTimeoutHandler;

class MockTest extends PHPUnit_Framework_TestCase {
    public function testHelloWorld() {
        $service = new Service();
        $service->addCallable(function ($name) {
            return 'hello ' . $name;
        }, 'hello');
        $server = new MockServer('testHelloWorld');
        $service->bind($server);
        $client = new Client(['mock://testHelloWorld']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
        $server->close();
    }
    public function testClientTimeout() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('timeout');
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $server = new MockServer('testClientTimeout');
        $service->bind($server);
        $client = new Client(['mock://testClientTimeout']);
        $client->timeout = 1000;
        $proxy = $client->useService();
        $proxy->wait(2);
        $server->close();
    }
    public function testServiceTimeout() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('timeout');
        $service = new Service();
        $service->addCallable(function ($time) {
            sleep($time);
        }, 'wait');
        $service->use([new ExecuteTimeoutHandler(1000), 'handler']);
        $server = new MockServer('testServiceTimeout');
        $service->bind($server);
        $client = new Client(['mock://testServiceTimeout']);
        $proxy = $client->useService();
        $proxy->wait(2);
        $server->close();
    }
    public function testMissingMethod() {
        $service = new Service();
        $service->addMissingMethod(function (string $name, array $args): string {
            return $name . json_encode($args);
        });
        $server = new MockServer('testMissingMethod');
        $service->bind($server);
        $client = new Client(['mock://testMissingMethod']);
        $proxy = $client->useService();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello["world"]');
        $server->close();
    }
}