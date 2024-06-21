<?php

namespace App\Command;

use App\RabbitMq\Producer;
use App\Redis\Action;
use App\Redis\RedisActionManager;
use RdKafka\Conf;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'generate:messages-bus'
)]
class GenerateMessageBusCommand extends Command
{
    private const OPTION_NUMBER_OF_MESSAGES = "nb";

    public function __construct(
        private readonly RedisActionManager $redisActionManager,
        private readonly Producer $rabbitmqProducer,
        private readonly SerializerInterface $serializer,
        private readonly ParameterBagInterface $params,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_NUMBER_OF_MESSAGES,);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numberOfMessageToGenerate = $this->getNumberOfMessages($input);
        for ($i = 0; $i < $numberOfMessageToGenerate; $i++) {
            $randomString = uniqid();
            $randomInt = rand(0, 9999);

            //$this->generateRedisAction($randomString, $randomInt);
            //$this->generateRabbitmqMessage($randomString, $randomInt);
            $this->generateKafkaMessage($randomString, $randomInt);
        }

        return Command::SUCCESS;
    }

    private function generateRedisAction(string $string, int $int): void
    {
        $action = new Action($string, $int);
        $this->redisActionManager->create($action);
    }

    private function generateRabbitmqMessage(string $string, int $int): void
    {
        $this->rabbitmqProducer->publish(
            $this->serializer->serialize(['string' => $string, 'int' => $int], 'json'),
            'messages'
        );
    }

    private function generateKafkaMessage(string $string, int $int): void
    {
        $conf = new Conf();
        $conf->set('log_level', (string) LOG_DEBUG);
        $conf->set('debug', 'all');

        $producer = new \RdKafka\Producer($conf);
        $producer->addBrokers($this->params->get('KAFKA_CLUSTER_BOOTSTRAP_ENDPOINT'));
        $topicProducer = $producer->newTopic('messages');

        $topicProducer->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            json_encode([
                'string' => $string, 'int' => $int
            ])
        );
        $producer->flush(1000);
    }

    private function getNumberOfMessages(InputInterface $input): int
    {
        $nb = $input->getOption(self::OPTION_NUMBER_OF_MESSAGES);
        if (!$nb) {
            return 500;
        }

        return $nb;
    }
}