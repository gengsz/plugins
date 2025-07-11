#!/bin/bash

# 检查版本号参数
if [ -z "$1" ]; then
  echo "❌ 请输入版本号，例如：./release.sh v1.0.3"
  exit 1
fi

VERSION=$1

echo "🚀 正在发布版本: $VERSION"

# 全部加入、提交
git add .
git commit -m "发布版本 $VERSION"

# 推送代码和标签
git push origin HEAD

# 打 tag 并推送
git tag $VERSION
git push origin $VERSION

echo "✅ 已成功推送 $VERSION"
