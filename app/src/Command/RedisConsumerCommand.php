<?php

namespace App\Command;

use App\Redis\RedisActionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'consumer:redis'
)]
class RedisConsumerCommand extends Command
{
    public function __construct(
        private readonly RedisActionManager $redisActionManager,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $actions = $this->redisActionManager->pull(50);
        while (count($actions) > 0) {
            $ids = [];
            foreach ($actions as $action) {
                $output->writeln(
                    sprintf('%s', json_encode($action))
                );
                $ids[] = $action->id;
            }

            $this->redisActionManager->deleteActions($ids);
            $actions = $this->redisActionManager->pull();
        }

        return Command::SUCCESS;
    }
}