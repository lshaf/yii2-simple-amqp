<?php

namespace lshaf\amqp;

use PhpAmqpLib\Wire\AMQPTable;
use yii\base\BaseObject;

/**
 * Class Option
 *
 * @author  L Shaf <shafry2008@gmail.com>
 * @package lshaf\amqp
 */
class ConfigOptions extends BaseObject
{
    public $queueName;
    public $namespace = "app\\jobs\\";
    
    public $autoDelete = false;
    public $passive = false;
    public $durable = true;
    public $exclusive = false;
    public $nowait = false;
    
    public $meta = [];
    
    public function init()
    {
        if (!($this->meta instanceof AMQPTable)) {
            $this->meta = new AMQPTable($this->meta);
        }
    }
}
