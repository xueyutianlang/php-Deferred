<?php
/**
 * Created by PhpStorm.
 * User: lan
 * Date: 16/4/12
 * Time: 下午3:33
 */

namespace Snuser\Deferred;


class Event
{
    private   $params = [];
    private   $pipe = null;

    public function __construct($params = [],$pipe=null)
    {
        $this->params = $params;
        $this->pipe = $pipe;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function currentDeferred(){
        if($this->pipe === null)return null;
        return $this->pipe->getDeferred();
    }
} 