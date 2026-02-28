#!/bin/bash
set -e

echo "=== Constraint Engine Bootstrap ==="

# Git init + first commit
git init
git add CLAUDE.md docs/ README.md bootstrap.sh .gitignore
git commit -m "init: CLAUDE.md and ALPS profile"

# GitHub repo creation (requires gh CLI)
if command -v gh &> /dev/null; then
  echo ""
  echo "Creating GitHub repository..."
  gh repo create constraint-engine --private --source=. --push
  echo "GitHub repo created."
else
  echo ""
  echo "gh CLI not found. Create repo manually:"
  echo "  gh repo create constraint-engine --private --source=. --push"
fi

echo ""
echo "Done. Next:"
echo ""
echo "  claude"
echo ""
echo "Then tell Claude Code:"
echo ""
echo "  CLAUDE.md を読んで、Phase A から順に実行して。"
echo "  BEAR.Skills プラグインはインストール済み。"
echo ""
