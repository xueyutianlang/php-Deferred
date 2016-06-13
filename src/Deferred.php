<?php
namespace Snuser\Deferred;

use Snuser\Deferred\Event;

/**
 * Created by PhpStorm.
 * User: lan
 * Date: 16/4/11
 * Time: 下午5:15
 */
class Deferred
{
    const PENDING = 'pending';
    const RESOLVED = 'resolved';
    const REJECTED = 'rejected';

    const DONE = 'done';
    const FAIL = 'fail';
    const PROCESS = 'process';

    private $state;
    private $doneProcessors = [];
    private $failProcessors = [];
    private $progressProcessors = [];
    private $doneEvent = null;
    private $failEvent = null;
    private $progressEvent = null;
    private $pipe = null;

    public function __construct()
    {
        $this->state = self::PENDING;
    }

    public function state()
    {
        return $this->state;
    }

    public function done($processor)
    {
        $this->isCallable($processor);
        if ($this->isResolved()) {
            $this->appendDoneProcessor($processor);
            $this->runDoneProcessors();
        } else {
            $this->appendDoneProcessor($processor);
        }
        return $this;
    }

    public function fail($processor)
    {
        $this->isCallable($processor);
        if ($this->isRejected()) {
            $this->appendFailProcessor($processor);
            $this->runFailProcessors();
        } else {
            $this->appendFailProcessor($processor);
        }
        return $this;
    }

    public function progress($processor)
    {
        $this->isCallable($processor);
        if ($this->isPending()) {
            $this->appendProgressProcessor($processor);
        }
        return $this;
    }

    final public function resovle($params = [])
    {
        if ($this->isRejected()) return false;
        $this->doneEvent = new Event($params, $this->pipe);
        $this->state = self::RESOLVED;
        $this->runDoneProcessors();
        return $this;
    }

    final public function reject($params = [])
    {
        if ($this->isResolved()) return false;
        $this->failEvent = new Event($params, $this->pipe);
        $this->state = self::REJECTED;
        $this->runFailProcessors();
        return $this;
    }

    final public function notify($params = [])
    {
        if ($this->isPending()) {
            $this->progressEvent = new Event($params, $this->pipe);
            $this->runProgressProcessors();
        }
        return $this;
    }

    final public function promise()
    {
        $promise = new Promise($this);
        return $promise;
    }

    public function pipe($doneProcessor = null, $failProcessor = null, $progressProcessor = null)
    {
        if ($doneProcessor === null) {
            $doneProcessor = new NullProcessor();
        }
        if ($failProcessor === null) {
            $failProcessor = new NullProcessor();
        }
        if ($progressProcessor === null) {
            $progressProcessor = new NullProcessor();
        }
        $this->appendDoneProcessor($doneProcessor);
        $this->appendFailProcessor($failProcessor);
        $this->appendProgressProcessor($progressProcessor);
        $deferred = new Deferred();
        $this->pipe = new Pipe($deferred);
        return $this->pipe->getDeferred()->promise();
    }

    /**
     * @param $processor
     * @throws DeferredException
     */
    protected function isCallable($processor)
    {
        if (is_callable($processor) === false) {
            throw new DeferredException("error[processor was not callablse]");
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function isRejected()
    {
        return $this->state === self::REJECTED;
    }

    /**
     * @return bool
     */
    protected function isResolved()
    {
        return $this->state === self::RESOLVED;
    }

    private function appendDoneProcessor($processor)
    {
        return array_push($this->doneProcessors, $processor);
    }

    private function appendFailProcessor($processor)
    {
        return array_push($this->failProcessors, $processor);
    }

    private function appendProgressProcessor($processor)
    {
        return array_push($this->progressProcessors, $processor);
    }

    private function runDoneProcessors()
    {
        $this->runProcessors($this->doneProcessors, $this->doneEvent);
        $this->doneProcessors = [];
        if ($this->pipe != null) {
            $this->pipe->deferredResovle();
        }
        return true;
    }

    private function runFailProcessors()
    {
        $this->runProcessors($this->failProcessors, $this->failEvent);
        $this->failProcessors = [];
        return true;
    }

    private function runProgressProcessors()
    {
        $this->runProcessors($this->progressProcessors, $this->progressEvent);
        return true;
    }

    private function runProcessors($processors, Event $event)
    {
        if (empty($processors)) return true;
        foreach ($processors as $processor) {
            call_user_func_array($processor, [$event]);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->state === self::PENDING;
    }
}

class DeferredException extends \Exception
{

}