---
title: 贡献代码
sidebar_position: 1
---

# 贡献代码

Typecho 是开源项目，代码托管在 GitHub 的 [typecho/typecho](https://github.com/typecho/typecho)。欢迎提交问题报告、修复和新功能。本页说明代码贡献的流程，以及仓库中的 CI 和发布流程实际做了什么。改进本文档见[贡献文档与翻译文档](./docs.md)，翻译 Typecho 界面见[翻译 Typecho 界面](./translate-typecho.md)。

## 行为准则

参与 Typecho 社区（Issue、Pull Request、论坛、Telegram 等）的所有人都要遵守仓库中的 [CODE_OF_CONDUCT.md](https://github.com/typecho/typecho/blob/master/CODE_OF_CONDUCT.md)。它采用 Contributor Covenant 行为准则：尊重他人、接受建设性的批评、不进行骚扰和人身攻击。违反准则的行为可以发邮件到 `admin@typecho.org` 向社区负责人报告，报告人的隐私会受到保护。

## 报告问题

在 [GitHub Issues](https://github.com/typecho/typecho/issues) 中新建 Issue 时可以选择三种模板：“上报 BUG”“提改进建议”“新功能提议”。报告 Bug 时按模板写清复现步骤和环境信息。使用中的疑问请到论坛 [forum.typecho.org](https://forum.typecho.org/) 提问，各种求助渠道见[获取帮助](../../guide/help/getting-help.md)。

安全漏洞**不要**提交公开的 Issue，见下文[报告安全漏洞](#security)。

## 提交 Pull Request

1. Fork [typecho/typecho](https://github.com/typecho/typecho) 并克隆到本地，按[开发环境与前端构建](../core/build.md)运行一个开发副本；
2. 从 `master` 新建分支。修复 Bug 的分支以 `fix/` 开头（例如 `fix/comment-page`），这关系到下文的自动合并；
3. 修改代码，遵守[编码规范](../core/coding-style.md)。改了 `admin/src/` 下的样式或脚本，要运行构建脚本并提交构建结果；改了数据库结构，要同时修改 `install/*.sql` 并写[升级脚本](../core/upgrade-scripts.md)；
4. 提交前在本地做一次语法检查（命令见下文 CI 一节）；
5. 向 `typecho/typecho` 的 `master` 分支发起 Pull Request。PR 描述模板有两部分：`Fixes #`（关联的 Issue 编号）和“Proposed Changes”（改动列表）。

仓库没有单元测试，CI 只做语法检查。请在 PR 中说明你是怎样验证这个改动的。

## CI：只做语法检查 {#ci}

工作流 `.github/workflows/Typecho-dev-Ci.yml`（名称“Typecho Dev Test”）在两种情况下运行：推送到 `master`，以及向 `master` 发起或更新 Pull Request。

它包含两个任务：

**PHP 语法检查**：分别用 PHP 7.4、8.0、8.1、8.2 对仓库中的每个 `.php` 文件运行 `php -l -n`，任何一个文件在任何一个版本上有语法错误，该版本的任务就失败（四个版本互不影响，各自报告结果）。命令如下，可以在本地照样运行：

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l -n | (! grep -v "No syntax errors detected" )
```

`php -l` 只检查语法，不执行代码，所以调用了不存在的函数、类型不匹配等运行时错误不会被发现。

**构建开发版**：只在推送到 `typecho/typecho` 仓库的 `master` 时运行（PR 不运行），并且要在语法检查通过之后。它把程序打成 `typecho.zip`，上传到 GitHub 上名为“CI Build”（标签 `ci`）的 Release，覆盖上一次的文件，即开发版。

推送到 `master` 的提交信息中包含 `skip ci` 时，两个任务都会跳过。

## fix/\* 分支自动合入 dev {#dev}

仓库有一个 `dev` 分支。工作流 `.github/workflows/Typecho-hotfix-Merge.yml`（名称“Typecho Hotfix Merge”）在向 `master` 的 Pull Request 关闭或被加上标签时运行：

- PR **被合并**，且它的源分支名以 `fix/` 开头时，把这个分支也合并进 `dev`；
- 源分支名不以 `fix/` 开头的 PR，在被加上 `merge-to-dev` 标签时，把它的源分支合并进 `dev`。

合并按分支名在 `typecho/typecho` 仓库中进行。这样 `master` 上的修复会同步到 `dev`，不需要手动再提交一次。

## 发布流程 {#release}

1. 修改 `var/Typecho/Common.php` 中的 `Common::VERSION`；需要迁移数据时在 `var/Utils/Upgrade.php` 中加入对应版本的升级方法，见[编写升级脚本](../core/upgrade-scripts.md)；
2. 在 GitHub 上发布 Release（标签形如 `v1.3.0`）；
3. 工作流 `.github/workflows/Typecho-release-Ci.yml` 在 Release 发布时运行：打包 `typecho.zip` 并作为附件上传到这个 Release；然后触发 `typecho/languages` 仓库的 `update.yml` 工作流，用新代码重新生成 `messages.pot`，见[翻译 Typecho 界面](./translate-typecho.md)。

开发版和正式版的 `typecho.zip` 打包方式相同：只包含 `LICENSE.txt`、`index.php`、`install.php`、`admin/`、`install/`、`usr/`、`var/`，新建 `usr/uploads/` 目录（权限 755），去掉 `admin/src/` 和 `usr/themes/classic-22/static/scss/`。`tools/`、`README.md`、`.github/` 等都不在包中。

## 报告安全漏洞 {#security}

仓库中的 `SECURITY.md` 规定：安全漏洞通过邮件报告到 **`security@typecho.org`**。

请不要在 GitHub Issues、论坛或其他公开场合披露漏洞细节。邮件中写明受影响的版本、漏洞类型、复现步骤和可能的影响，便于维护者确认和修复。站长侧的安全建议见[安全加固](../../guide/operations/security.md)。

## 贡献者

[![贡献者](https://contrib.rocks/image?repo=typecho/typecho)](https://github.com/typecho/typecho/graphs/contributors)
