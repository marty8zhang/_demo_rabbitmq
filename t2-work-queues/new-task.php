<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Declare a queue.
// A queue will only be created if it doesn't exist already.
// $channel->queue_declare('hello', false, false, false, false);
$channel->queue_declare('task_queue', false, true, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage(
    $data,
    // With the persistent delivery mode, messages won't be lost, even if the RabbitMQ server crashes.
    // Note: The persistence guarantees aren't strong, messages are still possible to be lost by this means.
    array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
);

$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent {$data}\n";

$channel->close();
$connection->close();
