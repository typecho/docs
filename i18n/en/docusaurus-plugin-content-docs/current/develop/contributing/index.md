---
title: Contributing Code
sidebar_position: 1
translation: machine
source_commit: 8e3ee54cc8774bccb3ee80aae02ff77229f90176
---

# Contributing Code

Typecho is an open-source project whose code is hosted on GitHub at [typecho/typecho](https://github.com/typecho/typecho). Bug reports, fixes and new features are welcome. This page explains how to contribute code, and what the CI and release workflows in the repository actually do. To improve this documentation, see [Contributing to and translating the documentation](./docs.md); to translate the Typecho interface, see [Translating the Typecho interface](./translate-typecho.md).

## Code of conduct

Everyone who takes part in the Typecho community (issues, pull requests, the forum, Telegram and so on) must follow the [CODE_OF_CONDUCT.md](https://github.com/typecho/typecho/blob/master/CODE_OF_CONDUCT.md) in the repository. It adopts the Contributor Covenant: respect others, accept constructive criticism, and do not harass or attack anyone personally. Violations can be reported to the community leaders by email at `admin@typecho.org`; the reporter's privacy is protected.

## Reporting issues

When you open a new issue in [GitHub Issues](https://github.com/typecho/typecho/issues), you can choose from three templates: "Report a bug", "Suggest an improvement" and "Propose a new feature". When reporting a bug, follow the template and give clear reproduction steps and environment information. Ask usage questions on the forum at [forum.typecho.org](https://forum.typecho.org/); for all the help channels, see [Getting help](../../guide/help/getting-help.md).

Do **not** file security vulnerabilities as public issues; see [Reporting security vulnerabilities](#security) below.

## Submitting a pull request

1. Fork [typecho/typecho](https://github.com/typecho/typecho), clone it locally, and run a development copy as described in [Development environment and front-end build](../core/build.md);
2. Create a branch from `master`. Branches that fix bugs start with `fix/` (for example `fix/comment-page`); this matters for the automatic merge described below;
3. Make your changes, following the [coding style](../core/coding-style.md). If you change styles or scripts under `admin/src/`, run the build scripts and commit the build output; if you change the database structure, also change `install/*.sql` and write an [upgrade script](../core/upgrade-scripts.md);
4. Run a syntax check locally before committing (the command is in the CI section below);
5. Open a pull request against the `master` branch of `typecho/typecho`. The PR description template has two parts: `Fixes #` (the related issue number) and "Proposed Changes" (the list of changes).

The repository has no unit tests; CI only checks syntax. Please explain in the PR how you verified the change.

## CI: syntax checks only {#ci}

The workflow `.github/workflows/Typecho-dev-Ci.yml` (named "Typecho Dev Test") runs in two cases: on pushes to `master`, and when a pull request against `master` is opened or updated.

It contains two jobs:

**PHP syntax check**: runs `php -l -n` on every `.php` file in the repository with PHP 7.4, 8.0, 8.1 and 8.2 separately. If any file has a syntax error on any version, the job for that version fails (the four versions are independent and each reports its own result). The command is as follows, and you can run it locally as is:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l -n | (! grep -v "No syntax errors detected" )
```

`php -l` only checks syntax and does not execute code, so runtime errors such as calls to functions that do not exist or type mismatches are not caught.

**Development build**: runs only on pushes to `master` of the `typecho/typecho` repository (not for PRs), and only after the syntax check passes. It packs the program into `typecho.zip` and uploads it to the GitHub release named "CI Build" (tag `ci`), overwriting the previous file; this is the development version.

When the commit message of a push to `master` contains `skip ci`, both jobs are skipped.

## fix/\* branches are merged into dev automatically {#dev}

The repository has a `dev` branch. The workflow `.github/workflows/Typecho-hotfix-Merge.yml` (named "Typecho Hotfix Merge") runs when a pull request against `master` is closed or labeled:

- When the PR **is merged** and its source branch name starts with `fix/`, that branch is also merged into `dev`;
- For a PR whose source branch name does not start with `fix/`, its source branch is merged into `dev` when the PR gets the `merge-to-dev` label.

The merge is done by branch name in the `typecho/typecho` repository. This way fixes on `master` are carried over to `dev` without being committed again by hand.

## Release process {#release}

1. Change `Common::VERSION` in `var/Typecho/Common.php`; when data needs migrating, add an upgrade method for that version to `var/Utils/Upgrade.php`; see [Writing upgrade scripts](../core/upgrade-scripts.md);
2. Publish a release on GitHub (with a tag such as `v1.3.0`);
3. The workflow `.github/workflows/Typecho-release-Ci.yml` runs when the release is published: it packs `typecho.zip` and uploads it to the release as an asset; it then triggers the `update.yml` workflow of the `typecho/languages` repository, which regenerates `messages.pot` from the new code; see [Translating the Typecho interface](./translate-typecho.md).

The development and official `typecho.zip` are packed the same way: they contain only `LICENSE.txt`, `index.php`, `install.php`, `admin/`, `install/`, `usr/` and `var/`, create a `usr/uploads/` directory (permissions 755), and leave out `admin/src/` and `usr/themes/classic-22/static/scss/`. `tools/`, `README.md`, `.github/` and so on are not in the package.

## Reporting security vulnerabilities {#security}

The repository's `SECURITY.md` specifies that security vulnerabilities are reported by email to **`security@typecho.org`**.

Do not disclose vulnerability details in GitHub Issues, on the forum or anywhere else in public. In the email, give the affected versions, the type of vulnerability, the steps to reproduce and the possible impact, so that the maintainers can confirm and fix it. For security advice for site owners, see [Security hardening](../../guide/operations/security.md).

## Contributors

[![Contributors](https://contrib.rocks/image?repo=typecho/typecho)](https://github.com/typecho/typecho/graphs/contributors)
