<?php

namespace App\RabbitMq;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Consumer implements ConsumerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    )
    {
    }

    public function execute(AMQPMessage $msg): void
    {
        $this->logger->info($msg->body);
        //$msg->ack(); // Envoi du fait que le message est bien consommé.
    }
}