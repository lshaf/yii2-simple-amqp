<?php

namespace lshaf\amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;

/**
 * Class Queue
 *
 * @author  L Shaf <shafry2008@gmail.com>
 * @package lshaf\amqp
 *          
 * @property \PhpAmqpLib\Channel\AMQPChannel $channel
 */
class Queue extends BaseObject
{
    public $host;
    public $username;
    public $password;
    public $port;
    public $vhost = '/';

    public $queueName;
    public $autoDelete = false;
    public $passive = false;
    public $durable = true;
    public $exclusive = false;
    public $nowait = false;
    public $options = [];
    
    /** @var AMQPStreamConnection */
    private $_connection;
    public function init()
    {
        $this->_connection = new AMQPStreamConnection(
            $this->host, 
            $this->port, 
            $this->username,
            $this->password,
            $this->vhost
        );
    }
    
    private $_declaredChannel = [];
    public function getChannel($channel_id = null)
    {
        $defaultOptions = [
            'x-max-priority' => 10
        ];

        $channel = $this->_connection->channel($channel_id);
        $options = new AMQPTable(ArrayHelper::merge($defaultOptions, $this->options));
        
        if (in_array($this->_declaredChannel, $channel_id)) {
            $channel->queue_declare(
                $this->queueName,
                $this->passive,
                $this->durable,
                $this->exclusive,
                $this->autoDelete,
                $this->nowait,
                $options
            );
        }

        $this->_declaredChannel[] = $channel_id;
        return $channel;
    }
    
    /**
     * @param string|array $data
     */
    public function send($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        } else if (!is_string($data)) {
            throw new InvalidArgumentException("Param data only receive array or string");
        }

        $channel = $this->channel;
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, '', $this->queueName);
    }
    
    public function listen($callback)
    {
        $channel = $this->channel;
        $channel->basic_consume($this->queueName, '', false, true, $this->exclusive, $this->nowait, $callback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }
}
