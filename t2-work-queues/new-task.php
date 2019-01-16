<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

/*
 * Declare a queue. A queue will only be created if it doesn't exist already.
 * Notes:
 *     - RabbitMQ doesn't allow you to re-define an existing queue with different parameters and will return an error to
 *       any program that tries to do that.
 *     - The 3rd parameter indicates if the queue is durable (won't be lost even if the RabbitMQ server crashes) or not.
 *       The persistence guarantees aren't strong, messages are still possible to be lost by this means. E.g., messages
 *       were only saved to cache, or the server shuts down before/when messages are being saved to the disk.
 */
$channel->queue_declare('task_queue', false, true, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage(
    $data,
    // With the persistent delivery mode, messages won't be lost, even if the RabbitMQ server crashes.
    array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
);

$channel->basic_publish($msg, '', 'task_queue');

echo " [x] Sent {$data}\n";

$channel->close();
$connection->close();
