<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

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

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] Received "', $msg->body, "\"\n";
    sleep(substr_count($msg->body, '.') * 5);
    echo " [x] Done\n";
    /*
     * Send back the acknowledgement when the task has been done successfully.
     * Acknowledgement must be sent on the same channel where the delivery was received on. Attempts to acknowledge
     * using a different channel will result in a channel-level protocol exception.
     */
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

// The below line tells RabbitMQ not to give more than one message to a worker at a time. The 2nd parameter is the
// consumer prefetch count.
$channel->basic_qos(null, 1, null);

// The 4th parameter indicates if the "no acknowledgement" flag is on or not. If the consumer dies without sending an
// ack, RabbitMQ will understand that a message wasn't processed fully and will re-queue it.
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

// The below code will block while $channel has callbacks.
while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
