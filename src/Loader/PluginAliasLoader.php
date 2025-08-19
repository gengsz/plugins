<?php
namespace Gengsz\Plugins\Loader;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

/**
 * 高级插件别名加载器：Trait 预加载 + Class 懒加载
 */
class PluginAliasLoader
{
    protected static string $baseDir = __DIR__ . '/../Plugin';
    protected static string $cacheFile = __DIR__ . '/../../cache/alias_cache.php';
    protected static array $classMap = [];

    public static function init(): void
    {
        if (file_exists(self::$cacheFile)) {
            self::$classMap = include self::$cacheFile;
        } else {
            self::buildAliasCache();
        }

        self::preloadTraitFilesOnly();

        spl_autoload_register([self::class, 'autoloadAlias'], true, true);
        spl_autoload_register([self::class, 'autoloadNamespace'], true, false);
    }

    public static function buildAliasCache(): void
    {
        $map = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$baseDir));

        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $path = $file->getPathname();
                $relPath = substr($path, strlen(self::$baseDir)+1, -4); // 去掉 .php
                $parts = explode(DIRECTORY_SEPARATOR, $relPath);
                $className = array_pop($parts);
                if ($parts) {
                    $namespace = 'Gengsz\\Plugins\\Plugin\\' . implode('\\', $parts);
                } else {
                    $namespace = 'Gengsz\\Plugins\\Plugin';
                }
                $fqcn = $namespace . '\\' . $className;

                // 是否是 trait，简单判断：含有 trait Foo
                $contents = file_get_contents($path, false, null, 0, 128);
                $type = (preg_match('/\btrait\b/', $contents)) ? 'trait' : 'class';

                $map[$className] = ['fqcn' => $fqcn, 'type' => $type];
            }
        }

        file_put_contents(self::$cacheFile, '<?php return ' . var_export($map, true) . ';');
        self::$classMap = $map;
        //echo "✅ Alias cache built: " . self::$cacheFile . PHP_EOL;
    }

    public static function preloadTraitFilesOnly(): void
    {
        foreach (self::$classMap as $alias => $info) {
            if ($info['type'] !== 'trait') continue;

            $fqcn = $info['fqcn'];
            $file = self::classToFile($fqcn);

            if ($file && file_exists($file)) {
                require_once $file;
                if (!trait_exists($fqcn, false)) continue;

                if (!trait_exists($alias, false)) {
                    class_alias($fqcn, $alias);
                }
            }
        }
    }

    public static function autoloadAlias($class): bool
    {
        if (!is_string($class) || !isset(self::$classMap[$class])) return false;

        $fqcn = self::$classMap[$class]['fqcn'];
        $type = self::$classMap[$class]['type'];

        if (!class_exists($fqcn, false) && !interface_exists($fqcn, false)) {
            self::autoloadNamespace($fqcn);
        }

        if ((class_exists($fqcn, false) || interface_exists($fqcn, false))
            && !class_exists($class, false) && $type !== 'trait') {
            class_alias($fqcn, $class);
        }

        return true;
    }

    public static function autoloadNamespace(string $class): bool
    {
        if (strpos($class, 'Gengsz\\Plugins\\') !== 0) return false;

        $file = self::classToFile($class);
        if ($file && file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }

    protected static function classToFile(string $class): ?string
    {
        $prefix = 'Gengsz\\Plugins\\';
        $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        return __DIR__ . '/../' . $path . '.php';
    }
}
