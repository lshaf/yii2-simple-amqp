<?php

namespace lshaf\amqp;

use yii\base\BaseObject;

/**
 * Class AMQPAbstract
 *
 * @author  L Shaf <shafry2008@gmail.com>
 * @package lshaf\amqp
 *
 * @property string $command
 * @property mixed  $data
 * @property mixed  $params
 */
abstract class AMQPAbstract extends BaseObject
{
    public $command;
    public $data;
    public $params;
    
    abstract public function execute();
}
