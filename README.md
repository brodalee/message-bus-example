# Message Bus

Pour générer des messages : 

```bash
php bin/console generate:messages-bus
```

## Redis

## Rabbitmq

Setup : 
```bash
php bin/console rabbitmq:setup-fabric
```

Consommation des messages :

```bash
php bin/console rabbitmq:consume message_bus
```

## Kafka

### Topics
Pour créer un topic, aller dans AKHQ ( localhost:8030 ), menu de gauche > Topics > Créer un topic.

### Consumer Group
Création d'un topic : 

Dans le container kafka:
```bash
bin/kafka-topics.sh --create --topic messages --bootstrap-server kafka:9092 --partitions 1 --replication-factor 1
```

docker compose exec kafka -it bash
```bash
# Pour voir les topics
bin/kafka-topics.sh --list --bootstrap-server="kafka:9092"

# Voir le nb de message dans le topic messages
bin/kafka-run-class.sh kafka.tools.GetOffsetShell --broker-list kafka:9092 --topic messages
```

