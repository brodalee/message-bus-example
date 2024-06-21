<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'consumer:kafka'
)]
class KafkaConsumerCommand extends Command
{
    private const OPTION_TOPIC_NAME = 'topic';
    private const TIMEOUT = 1000 * 60 * 2; // 2 minutes avant timeout.


    public function __construct(
        private readonly ParameterBagInterface $params,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(self::OPTION_TOPIC_NAME, null, InputOption::VALUE_REQUIRED, 'Topic\'s name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topicName = $input->getOption(self::OPTION_TOPIC_NAME);
        $consumer = new KafkaConsumer($this->getKafkaConfiguration());
        $consumer->subscribe([$topicName]);
        $output->writeln(
            sprintf('%s', "Topic :: " . $topicName)
        );

        try {
            while (true) {
                $message = $consumer->consume(self::TIMEOUT);
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $output->writeln(
                            sprintf(
                                '%s', $message->payload
                            )
                        );
                        // Envoi à kafka le fait qu'on a bien lu le message
                        // Et donc on passe au prochain offset
                        $consumer->commit($message);
                        break;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        // Si on est timeout ou bien qu'il n'y a plus aucun message à consommer, on arrête la commande.
                        return Command::SUCCESS;
                    default:
                        throw new \Exception($message->errstr(), $message->err);
                }
            }
        } finally {
            $consumer->close();
        }
    }

    private function getKafkaConfiguration(): Conf
    {
        $kafkaUrl = (string) $this->params->get('KAFKA_CLUSTER_BOOTSTRAP_ENDPOINT');
        $conf = new Conf();
        $conf->set('group.id', (string) $this->params->get('KAFKA_CDC_EVENT_CONSUMER_GROUP_ID'));
        $conf->set('metadata.broker.list', $kafkaUrl);
        $conf->set('enable.auto.commit', 'false'); // On commit seulement une fois que le message a bien été lu.
        $conf->set('auto.offset.reset', 'latest');

        return $conf;
    }
}