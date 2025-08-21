#!/bin/bash

# 获取最新 tag（按版本排序）
LATEST_TAG=$(git tag | sort -V | tail -n 1)

# 初始 tag 防御
if [[ -z "$LATEST_TAG" ]]; then
  LATEST_TAG="v1.0.0"
fi

# 解析 v1.1.5 => 1.1.5
LATEST_VER=${LATEST_TAG#v}
IFS='.' read -r major minor patch <<< "$LATEST_VER"
NEXT_VER="v$major.$minor.$((patch + 1))"

# 用户传参优先
VERSION=${1:-$NEXT_VER}

read -p "是否继续打包？[y/N]: " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "❌ 操作已取消"
    exit
fi

echo "🚀 正在发布版本: $VERSION"

# 构建 alias 缓存
if [ -f tools/build-alias-cache.php ]; then
  echo "🔧 生成 alias_cache.php..."
  php tools/build-alias-cache.php
fi

# 检查是否有文件要提交
if ! git diff --quiet || ! git diff --cached --quiet; then
  git add .
  git commit -m "发布版本 $VERSION"
  git push origin master
else
  echo "⚠️ 无变更，跳过 commit 和 push"
fi

# 打 tag 并推送（如果不存在）
if git rev-parse "$VERSION" >/dev/null 2>&1; then
  echo "⚠️ Tag $VERSION 已存在，跳过打 tag"
else
  git tag $VERSION
  git push origin $VERSION
fi

echo "✅ 发布完成：$VERSION"

