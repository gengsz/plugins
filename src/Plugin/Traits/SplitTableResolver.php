<?php
namespace Gengsz\Plugins\Plugin\Traits;
use Gengsz\Plugins\Config\PluginConfig;

trait SplitTableResolver {

    protected static function getModelClass($uid) {
        $prefix = static::modelPrefix();
        $file   = static::callerDir(). "/{$prefix}00.php";

        if (!class_exists($prefix.'00')) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new \RuntimeException("找不到文件：{$prefix}00");
            }
        }

        $hash   = static::tableHash($uid, $prefix, $file);     // 可换成任意逻辑
        $class  = $prefix. $hash;

        return $class;
    }

    protected static function countSubModels(string $prefix, string $file): int {
        static $shardMap = [];
        if (isset($shardMap[$prefix])) return $shardMap[$prefix];

        // 优先使用注入的回调
        if (is_callable(PluginConfig::$shardLoader)) {
            return call_user_func(PluginConfig::$shardLoader, $prefix);
        }

        // fallback：扫描 class
        if (!file_exists($file)) return $shardMap[$prefix] = 1;

        $content = file_get_contents($file);
        preg_match_all('/class\\s+' . preg_quote($prefix) . '(\\d{2})\\s+extends/', $content, $matches);

        $max = !empty($matches[1]) ? count($matches[1]) : 1;

        return $shardMap[$prefix] = $max;
    }

    protected static function tableHash($uid, $modelPrefix, $file) {
        $tableCount = static::countSubModels($modelPrefix, $file);
        return str_pad($uid % $tableCount, 2, '0', STR_PAD_LEFT);
    }

    protected static function callerDir(): string {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        return dirname($bt[1]['file']);
    }

    protected static function modelPrefix(): string {
        $ref = new \ReflectionClass(static::class);
        $instance = $ref->newInstanceWithoutConstructor(); // 不走构造函数
        return $instance->tableName();  // ✅ 调用实例方法
    }
}
