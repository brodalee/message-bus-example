<?php

namespace App\Redis;

use Predis\Client;
use Symfony\Component\Serializer\SerializerInterface;

class RedisActionManager
{
    public function __construct(
        private readonly Client $client,
        private readonly SerializerInterface $serializer,
    )
    {
    }

    public function create(Action $action): void
    {
        $this->client->xadd(
            'stream',
            [
                'action' => $action::class,
                'data' => $this->serializer->serialize($action, 'json'),
            ],
            "*"
        );
    }

    /**
     * @param int $numberOfActions
     * @return Action[]
     */
    public function pull(int $numberOfActions = 1): array
    {
        $data = $this->client->xrange('stream', '-', '+', $numberOfActions);
        $actions = [];
        foreach ($data as $score => $actionData) {
            $action = $this->serializer->deserialize($actionData['data'], $actionData['action'], 'json');
            $action->id = $score;
            $actions[] = $action;
        }

        return $actions;
    }

    public function deleteAction(string $id): void
    {
        $this->client->xdel('stream', $id);
    }

    public function deleteActions(array $ids): void
    {
        $this->client->xdel('stream', ...$ids);
    }
}