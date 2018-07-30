<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// The 3rd parameter indicates if the queue is durable (won't be lost even if the RabbitMQ server crashes) or not.
// Note: RabbitMQ doesn't allow you to redefine an existing queue with different parameters and will return an error to any program that tries to do that.
// $channel->queue_declare('hello', false, false, false, false);
$channel->queue_declare('task_queue', false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    // Send back the acknowledgement when the task has been done successfully.
    // Acknowledgement must be sent on the same channel where the delivery was received on. Attempts to acknowledge using a different channel will result in a channel-level protocol exception.
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

// The 2nd parameter is the consumer prefetch count.
// The below line tells RabbitMQ not to give more than one message to a worker at a time.
$channel->basic_qos(null, 1, null);
// The 4th parameter indicates if the "no acknowledgement" flag is on or not.
$channel->basic_consume('hello', '', false, false, false, false, $callback);

// The below code will block while $channel has callbacks.
while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
