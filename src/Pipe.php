<?php
/**
 * Created by PhpStorm.
 * User: lan
 * Date: 16/4/13
 * Time: 下午4:12
 */

namespace Snuser\Deferred;


class Pipe {
    private $deferred;

    public function __construct(Deferred $deferred){
        $this->deferred = $deferred;
    }

    public function getDeferred(){
        return $this->deferred;
    }

    public function deferredResovle(){
        $this->deferred->resovle();
        return true;
    }
    public function deferredReject(){
        $this->deferred->reject();
        return true;
    }

} 