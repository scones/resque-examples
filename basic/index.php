<?php

echo time() . "\n";

require './vendor/autoload.php';

use Pimple\Psr11\ServiceLocator;
use Predis\Client;
use Resque\DataStore;
use Resque\Job;
use Resque\JsonSerializer;
use Resque\Resque;
use Resque\SignalHandler;
use Resque\Worker;

use ResqueExamples\Basic\BasicJob;

/**
 * extensive pimple setup
 * could be done in a few lines too...
 */

$container = new Pimple\Container();

$container['predis'] = function ($container) {
    $sentinels = ['tcp://redis-sentinel:26379'];
    $options = ['replication' => 'sentinel', 'service' => 'some-master'];
    return new Client($sentinels, $options);
};

$container['datastore'] = function ($container) {
    return new DataStore($container['predis']);
};

$container['serializer'] = function ($container) {
    return new JsonSerializer();
};

$container['resque'] = function ($container) {
    return new Resque($container['datastore'], $container['serializer']);
};

$container['signal_handler'] = function ($container) {
    return new SignalHandler();
};

$container[Job::class] = $container->protect(function ($queueName, $payload, $serviceLocator) {
    return new Job($queueName, $payload, $serviceLocator);
});

$container[BasicJob::class] = function ($container) {
    return new BasicJob();
};

$container['queue_names'] = ['basic_queue'];

$container['worker'] = function ($container) {
    $serviceLocator = new ServiceLocator($container, [BasicJob::class, Job::class]);
    $worker = new Worker($container['datastore'], $container['serializer'], $serviceLocator, $container['signal_handler']);
    $worker->setQueueNames($container['queue_names']);
    $worker->setInterval(0);
    return $worker;
};


// and now the easy part

// push a job
$resque = $container['resque'];
$resque->enqueue(BasicJob::class, ['foo' => 'bar'], 'basic_queue');

// work a job
$worker = $container['worker'];
$worker->work();
