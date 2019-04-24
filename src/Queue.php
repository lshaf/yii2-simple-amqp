<?php

namespace lshaf\amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;

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
    public $user;
    public $password;
    public $port;
    public $vhost = '/';
    
    /** @var \lshaf\amqp\ConfigOptions */
    public $options;
    
    /** @var AMQPStreamConnection */
    private $_connection;
    public function init()
    {
        $this->_connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );
        
        $this->options = new ConfigOptions($this->options);
    }
    
    private $_defaultChannel;
    private $_declaredChannel = [];
    public function getChannel($channel_id = null)
    {
        $channel_id = $channel_id ?? $this->_defaultChannel;
        $channel    = $this->_connection->channel($channel_id);
        if (is_null($channel_id)) {
            $this->_defaultChannel = $channel_id = $channel->getChannelId();
        }
    
        if (!in_array($channel_id, $this->_declaredChannel)) {
            $channel->queue_declare(
                $this->options->queueName,
                $this->options->passive,
                $this->options->durable,
                $this->options->exclusive,
                $this->options->autoDelete,
                $this->options->nowait,
                $this->options->meta
            );
        }
        
        $this->_declaredChannel[] = $channel_id;
        return $channel;
    }
    
    /**
     * @param string|array $data
     * @param string       $exchange
     * @param string       $key
     */
    public function send($data, $exchange = '', $key = '')
    {
        if (is_array($data)) {
            $data = json_encode($data);
        } else if (!is_string($data)) {
            throw new InvalidArgumentException("Param data only receive array or string");
        }
        
        $channel = $this->channel;
        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, $exchange, $key);
    }
    
    private $_declaredExchange = [];
    public function bindExchange($exchange, $type, array $key = [])
    {
        $keyExchange = "{$exchange}.{$type}";
        $channel = $this->channel;
        
        if (!in_array($keyExchange, $this->_declaredExchange)) {
            $channel->exchange_declare(
                $exchange,
                $type,
                $this->options->passive,
                $this->options->durable,
                $this->options->autoDelete,
                false,
                $this->options->nowait
            );
        }

        if (count($key) == 0) {
            $channel->queue_bind(
                $this->options->queueName,
                $exchange
            );
        } else {
            foreach ($key as $route) {
                $channel->queue_bind(
                    $this->options->queueName,
                    $exchange,
                    $route
                );
            }
        }
    }
    
    public function listen($callback)
    {
        $channel = $this->channel;
        $channel->basic_consume(
            $this->options->queueName,
            '',
            false,
            true,
            $this->options->exclusive,
            $this->options->nowait,
            $callback
        );
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }
    
    private function write_log($text)
    {
        $date = date("Y-m-d H:i:s");
        echo "[{$date}] {$text}\n";
    }
    
    public function process($debug = false)
    {
        $this->listen(function (AMQPMessage $msg) use ($debug) {
            $appName = 'amqp';
            $json = @json_decode($msg->getBody(), true);
            try {
                if ($debug) {
                    $this->write_log("[#] RECEIVED {$msg->delivery_info['routing_key']}", $appName);
                }
                
                if (!$json) {
                    throw new \Exception('ERROR DECODE JSON');
                }
                
                $key = $msg->delivery_info['routing_key'];
                $namespace = rtrim($this->options->namespace, "\\");
                $className = $namespace . "\\" . ucfirst($key) . "Job";
                
                if (!class_exists($className)) {
                    throw new \Exception("Class {$className} is not exist");
                }
                
                $instance = new $className($json);
                if (!($instance instanceof AMQPAbstract)) {
                    throw new \Exception("Class {$className} must be instance of " . AMQPAbstract::class);
                }
                
                $instance->execute();
                if ($debug) {
                    $this->write_log("RUN command in {$className}");
                }
            } catch (\Exception $e) {
                if ($debug) {
                    $this->write_log($msg->getBody(), $appName);
                    $this->write_log($e->getMessage(), $appName);
                    $this->write_log($e->getTraceAsString(), $appName);
                }
            }
        });
    }
}
