# Yii2 Simple AMQP

Yii2 Simple AMQP is a simple plugin for Yii2 to connect with RabbitMq using phpAmqpLib.

## Installation

Use the [Composer](https://getcomposer.org/) to install Yii2 Simple AMQP.

Add package in composer.json 

```json
        "lshaf/yii2-simple-amqp": "~0.0.0"
```

then, in repositories section add 

```json
        
    "repositories": [
        ....
        {
            "type": "vcs",
            "url": "https://github.com/lshaf/yii2-simple-amqp.git"
        }
    ]
```
And do composer update 

## Usage

add this config inside components section in main.php 

```php
'components' => [
      ...
      'queue' => [
                  'class'     => lshaf\amqp\Queue::class,
                  'host'      => 'yourhost',
                  'user'      => 'your_username',
                  'port'      => 5672,
                  'password'  => 'your_password',
                  'queueName' => 'your_queue_name'
              ]
        ]
```

###Send  
Send is method to publish message. To publish, execute:
```php
        $queue = \Yii::$app->queue;
        $queue->send($string, $exchange, $route);
```

- `$string` : your message
- `$exchange` : your exchange name, by default rabbitmq will create similar name queue
- `$route` : (optional) your routing key

###Listen  
Listen is method to subscribe message in queue. To listen, execute:
```php
        $queue = \Yii::$app->queue;
        $queue->listen(function (AMQPMessage $msg) {
            echo "your message : " . $msg->body;
        });
```
## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
Please make sure to update tests as appropriate.

## License
[MIT](https://choosealicense.com/licenses/mit/)