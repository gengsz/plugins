#!/bin/bash

# ✅ 清理非 vX.Y.Z 格式的旧 tag（如无 v 前缀的）
# 获取本地非 v 开头的 tag
#BAD_TAGS=$(git tag | grep -v '^v')
BAD_TAGS=$(git tag)

if [ -z "$BAD_TAGS" ]; then
  echo "✅ 没有非 v 开头的旧 tag，无需清理"
  exit 0
fi

echo "⚠️ 检测到以下非规范 tag，将执行删除："
echo "$BAD_TAGS"

read -p "是否继续删除本地和远程这些 tag？[y/N]: " confirm

if [[ "$confirm" =~ ^[Yy]$ ]]; then
  for tag in $BAD_TAGS; do
    echo "🧹 删除 tag: $tag"
    git tag -d "$tag"
    git push origin ":refs/tags/$tag"
  done
  echo "✅ 清理完成"
else
  echo "❌ 操作取消，未执行删除"
fi

