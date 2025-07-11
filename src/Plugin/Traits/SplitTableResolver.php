<?php
namespace Gengsz\Plugins\Plugin\Traits;

trait SplitTableResolver {
    protected static function resolveModel($uid) {
        $modelPrefix = static::modelPrefix(); // 子类提供，比如 'KPTask'
        $hash = static::tableHash($uid);     // 可换成任意逻辑
        $class = $modelPrefix . $hash;

        if (!class_exists($modelPrefix.'00')) {
            require_once __DIR__ . "/{$modelPrefix}00.php";
        }

        return $class;
    }

    protected static function tableHash($uid) {
        return str_pad($uid % 100, 2, '0', STR_PAD_LEFT); // 默认分100张表
    }

    // 必须由子类实现
    abstract protected static function modelPrefix();
}
