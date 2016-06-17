# php-Deferred
提供php在事务处理流程中的异构解决方案
实现借鉴jquery中deferred promise解决方案


###使用方式 请参考tests/deferredTest.php
```php
$deferred = new Deferred();
$function = function ($event) {
    $params = $event->getParams();
};
$deferred->done($function);
$deferred->resovle();

$deferred = new Deferred();
$function = function ($event) {
     $params = $event->getParams();
};
$deferred->fail($function);
$deferred->reject();


```


###安装方式
```
composer require snuser/deferred
```
