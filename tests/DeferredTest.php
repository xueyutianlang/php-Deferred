<?php
/**
 * Created by PhpStorm.
 * User: lan
 * Date: 16/4/11
 * Time: 下午6:30
 */

namespace Comos\Deferred;


class DeferredTest extends \PHPUnit_Framework_TestCase
{

    function testResovle()
    {
        $deferred = new Deferred();
        $this->assertEquals($deferred->state(), Deferred::PENDING);

        $function = function ($event) {
            $params = $event->getParams();
            $this->assertEquals($params, []);
        };
        $deferred->done($function);
        $deferred->resovle();
        $this->assertEquals($deferred->state(), Deferred::RESOLVED);
    }

    function testResovleWithParams()
    {
        $deferred = new Deferred();
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $args = ["a" => 1];
        $function = function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        };
        $function2 = function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        };
        $deferred->done($function);
        $deferred->done($function2);
        $deferred->resovle($args);
        $function3 = function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        };
        $deferred->done($function3);
        $this->assertEquals($deferred->state(), Deferred::RESOLVED);
    }

    function testReject()
    {
        $deferred = new Deferred();
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $function = function ($event) {
            $params = $event->getParams();
            $this->assertEquals($params, []);
        };
        $deferred->fail($function);
        $deferred->reject();
        $this->assertEquals($deferred->state(), Deferred::REJECTED);
    }

    function testRejectWithParams()
    {
        $deferred = new Deferred();
        $args = ["a" => 1, "b" => 1];
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $deferred->fail(function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        })->fail(function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        })->reject($args)->fail(function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($params, $args);
        });
        $this->assertEquals($deferred->state(), Deferred::REJECTED);
    }

    function testNotify()
    {
        $deferred = new Deferred();
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $function = function ($event) {
            $params = $event->getParams();
            $this->assertEquals($params, []);
        };
        $deferred->progress($function);
        $deferred->notify();
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $deferred->resovle();
        $this->assertEquals($deferred->state(), Deferred::RESOLVED);
    }

    function testNotifyWithParams()
    {
        $deferred = new Deferred();
        $this->assertEquals($deferred->state(), Deferred::PENDING);
        $function = function ($event) {
            $params = $event->getParams();
            $num = $params['time'] * 25;
            $this->assertEquals($num, $params['process']);
        };
        $deferred->progress($function);

        for ($i = 0; $i <= 4; $i++) {
            $params['time'] = $i;
            $params['process'] = $i * 25;
            $deferred->notify($params);
        }
        $deferred->resovle(["a" => 1]);
        $this->assertEquals($deferred->state(), Deferred::RESOLVED);
        $deferred->done(function ($event) {
            $params = $event->getParams();
            $this->assertEquals($params, ["a" => 1]);
        });
    }

    function testDeferredPromise()
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();
        $this->assertInstanceOf('Comos\Deferred\Promise', $promise);
    }

    function testPromiseResovle()
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();
        $this->assertEquals($promise->state(), Deferred::PENDING);
        $args = [1, 2, 3];
        $promise->done(function ($event) use ($args) {
            $params = $event->getParams();
            $this->assertEquals($args, $params);
        });
        $deferred->resovle($args);
        $this->assertEquals($promise->state(), Deferred::RESOLVED);
    }

    function testPromiseReject()
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();
        $this->assertEquals($promise->state(), Deferred::PENDING);
        $args = [1, 2, 3];
        $promise->fail(function ($event) use ($args) {
            $this->assertEquals($args, $event->getParams());
        });
        $deferred->reject($args);
        $this->assertEquals($promise->state(), Deferred::REJECTED);
    }

    function testPromiseNotify()
    {
        $deferred = new Deferred();
        $promise = $deferred->promise();
        $this->assertEquals($promise->state(), Deferred::PENDING);
        $function = function ($event) {
            $params = $event->getParams();
            $num = $params['time'] * 25;
            $this->assertEquals($num, $params['process']);
        };
        $promise->progress($function);
        for ($i = 0; $i <= 4; $i++) {
            $params['time'] = $i;
            $params['process'] = $i * 25;
            $deferred->notify($params);
        }
    }

    function testDeferredPipeEmpty()
    {
        $deferred = new Deferred();
        $step = $deferred->pipe(function () {
            });
        $this->assertInstanceOf('Comos\Deferred\Promise', $step);
    }

    function testDeferredPipe()
    {
        $deferred = new Deferred();
        $mock = $this->getMock('util', ['doneFunction', 'doneFunction2', 'doneFunction3']);
        $mock->expects($this->once())->method('doneFunction')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["a"], $event->getParams());
        });
        $mock->expects($this->once())->method('doneFunction2')->with()->willReturnCallback(function ($event) {
            $this->assertEquals([], $event->getParams());
            $event->currentDeferred()->resovle(["b"]);
        });
        $mock->expects($this->once())->method('doneFunction3')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["b"], $event->getParams());
            $event->currentDeferred()->resovle(["c"]);
        });


        $step = $deferred->pipe([$mock, 'doneFunction']);
        $step2 = $step->pipe([$mock, 'doneFunction2']);
        $step3 = $step2->pipe([$mock, 'doneFunction3']);
        $step3->done(function ($event) {
            $this->assertEquals(["c"], $event->getParams());
        });
        $deferred->resovle(["a"]);
    }

    function testDeferredPipe2()
    {
        $deferred = new Deferred();
        $mock = $this->getMock('util', ['doneFunction', 'doneFunction2']);
        $mock->expects($this->once())->method('doneFunction')->with()->willReturnCallback(function ($event) {
            $event->currentDeferred()->reject();
        });
        $mock->expects($this->never())->method('doneFunction2')->with()->willReturnCallback(function ($event) {
            $event->currentDeferred()->resovle();
        });
        $step = $deferred->pipe([$mock, 'doneFunction']);
        $step->pipe([$mock, 'doneFunction2']);
        $deferred->resovle();
    }

    function testDeferredPipeReject()
    {
        $deferred = new Deferred();
        $mock = $this->getMock('util', ['failFunction', 'failFunction2', 'failFunction3']);
        $mock->expects($this->once())->method('failFunction')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["a"], $event->getParams());
            $event->currentDeferred()->reject(["b"]);
        });
        $mock->expects($this->once())->method('failFunction2')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["b"], $event->getParams());
            $event->currentDeferred()->reject();
        });
        $mock->expects($this->once())->method('failFunction3')->with()->willReturnCallback(function ($event) {
            $event->currentDeferred()->reject();
        });

        $step = $deferred->pipe(null, [$mock, 'failFunction']);
        $step2 = $step->pipe(null, [$mock, 'failFunction2']);
        $step2->pipe(null, [$mock, 'failFunction3']);
        $deferred->reject(["a"]);
    }

    function testDeferredPipeNotify()
    {
        $deferred = new Deferred();
        $mock = $this->getMock('util', ['progressFunction', 'progressFunction2', 'progressFunction3']);
        $mock->expects($this->once())->method('progressFunction')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["a"], $event->getParams());
            $event->currentDeferred()->notify(["b"]);
            $event->currentDeferred()->notify(["b"]);
        });
        $mock->expects($this->atLeastOnce())->method('progressFunction2')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["b"], $event->getParams());
            $event->currentDeferred()->notify(["c"]);
            $event->currentDeferred()->notify(["c"]);
            $event->currentDeferred()->notify(["c"]);
        });
        $mock->expects($this->atLeastOnce())->method('progressFunction3')->with()->willReturnCallback(function ($event) {
            $this->assertEquals(["c"], $event->getParams());
        });

        $step = $deferred->pipe(null, null, [$mock, 'progressFunction']);
        $step2 = $step->pipe(null, null, [$mock, 'progressFunction2']);
        $step2->pipe(null, null, [$mock, 'progressFunction3']);
        $deferred->notify(["a"]);
    }

    /**
     * 构建场景测试
     * 1 追求女友 女友回复考虑一下
     *  拒绝阶段  ---死皮烂打
     *  女友考验阶段 --各种殷勤
     *  女友同意  ---xxxx
     *  女友拒绝 --- 一边哭去
     *
     */
    function testMultiDeferred()
    {
        $beautifulGrilDeferred = new Deferred();
        $man = $this->getMock('man', ['progress', 'success', 'fail']);
        $man->expects($this->atLeastOnce())->method('progress')->with()->willReturnCallback(function ($event) {
            $params = $event->getParams();
            $time = empty($params['time']) ? 1 : $params['time'];
            if ($time == 4) {
                $event->currentDeferred()->resovle();
            } else {
                echo "我会爱你的..." . $time . PHP_EOL;
                $time++;
                $event->currentDeferred()->notify(['time' => $time]);
                $time++;
                $event->currentDeferred()->notify(['time' => $time]);
                $time++;
                $event->currentDeferred()->notify(['time' => $time]);
            }
        });
        $man->expects($this->once())->method('success')->with()->willReturnCallback(function ($event) {
            echo "答应了我的追求！！" . PHP_EOL;
            $event->currentDeferred()->resovle();
        });
        $man->expects($this->once())->method('fail')->with()->willReturnCallback(function ($event) {
            echo "~~~~(>_<)~~~~我不会放弃的...." . PHP_EOL;
            $event->currentDeferred()->reject();
        });

        $reject = $beautifulGrilDeferred->pipe([$man, 'success'], [$man, 'fail'], [$man, 'progress']);
        $progress = $reject->pipe([$man, 'success'], [$man, 'progress']);
        $success = $progress->pipe([$man, 'success'], [$man, 'progress'], [$man, 'progress']);
        $success->pipe([$man, 'success']);

        $beautifulGrilDeferred->reject();
    }
}
 