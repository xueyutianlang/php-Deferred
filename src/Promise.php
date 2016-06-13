<?php
/**
 * Created by PhpStorm.
 * User: lan
 * Date: 16/4/12
 * Time: 下午5:04
 */

namespace Snuser\Deferred;


class Promise
{
    private $deferred = null;

    public function __construct(Deferred $deferred)
    {
        $this->deferred = $deferred;
    }

    public function done($processor)
    {
        $this->deferred->done($processor);
        return $this;
    }

    public function fail($processor)
    {
        $this->deferred->fail($processor);
        return $this;
    }

    public function progress($processor)
    {
        $this->deferred->progress($processor);
        return $this;
    }
    public function state(){
        return $this->deferred->state();
    }

    public function pipe($doneProcessor=null,$failProcessor=null,$progressProcessor=null){
        return $this->deferred->pipe($doneProcessor,$failProcessor,$progressProcessor);
    }
}