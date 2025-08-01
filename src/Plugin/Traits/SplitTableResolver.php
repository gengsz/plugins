<?php
namespace Gengsz\Plugins\Plugin\Traits;
use Gengsz\Plugins\Config\PluginConfig;

trait SplitTableResolver {

    public static function getModelClass($uid) {
        $prefix = static::modelPrefix();
        $file   = static::callerDir(). "/{$prefix}00.php";

        if (!class_exists($prefix.'00')) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                throw new \RuntimeException("The Sub Model File Is Not Found：{$prefix}00");
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
        if (is_callable(PluginConfig::$shardLoader) && $count = (int)call_user_func(PluginConfig::$shardLoader, $prefix)) {
            if ($count > 1) return $count;
        }

        // fallback：扫描 class
        $content = file_get_contents($file);
        preg_match_all('/class\\s+' . preg_quote($prefix) . '(\\d{2})\\s+extends/', $content, $matches);

        $count = !empty($matches[1]) ? count($matches[1]) : 0;
        if ($count < 1) throw new \RuntimeException("The Sub Model File Format Is Incorrect：{$prefix}00");

        return $shardMap[$prefix] = $count;
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

    protected static function fetchDataByGroup(array $tableMap, string $select, string $keyField, int $valid = 1): array {
        $datas = [];
        foreach ($tableMap as $cn => $ids) {
            $criteria = new \CDbCriteria;
            $criteria->select = $select;
            $criteria->addInCondition($keyField, $ids);
            $criteria->compare('valid', $valid);
            $ars = self::model($cn)->findAll($criteria);
            $as  = _ars_to_arrs($ars);
            $datas = array_merge($datas, $as);
        }
        return $datas;
    }
}
