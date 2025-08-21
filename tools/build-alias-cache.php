<?php
require_once __DIR__ . '/../src/Loader/PluginAliasLoader.php';
Gengsz\Plugins\Loader\PluginAliasLoader::buildAliasCache();
echo "✅ alias_cache.php 构建完成\n";
