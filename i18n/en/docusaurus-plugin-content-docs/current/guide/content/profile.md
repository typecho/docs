---
title: Profile
sidebar_position: 10
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Profile

Every user who can log in to the admin panel has **Dashboard → Profile** (控制台 → 个人设置, `admin/profile.php`); you can also open it by clicking your own name in the top right corner. What you change here is only **your own** profile and preferences; other users are not affected.

The left side of the page shows your avatar, nickname, "You have N posts, and N comments about you in N categories" (目前有 N 篇日志, 并有 N 条关于你的评论在 N 个分类中) and your last login time; the right side contains the following blocks, from top to bottom.

## Avatar {#avatar}

Typecho does not store avatar images. Avatars in the admin panel and on the front end all come from [Gravatar](https://gravatar.com/), looked up by your **email address**:

- To change your avatar, register on gravatar.com with the same email and upload an avatar; clicking the avatar on the Profile page (tooltip "Change avatar on Gravatar" (在 Gravatar 上修改头像)) also opens Gravatar;
- If you change your email address, the avatar changes with it; when the email has no avatar on Gravatar, the default grey silhouette is shown;
- The avatar URL is the Gravatar URL plus the MD5 of the email (trimmed and lowercased); when the site uses HTTPS, `https://secure.gravatar.com` is used, otherwise `http://www.gravatar.com`;
- When Gravatar is slow to reach from mainland China, you can switch to a mirror with the `__TYPECHO_GRAVATAR_PREFIX__` constant, see [Configuration file](/guide/operations/config-file).

## Personal information

| UI name | Field | Description |
|---|---|---|
| Nickname (昵称) | `screenName` | The name shown on the front end; must not duplicate another user's nickname |
| Homepage URL (个人主页地址) | `url` | Must start with `http://` or `https://`; since 1.3, internationalized domain names (such as Chinese ones) are supported |
| Email address * (邮件地址) | `mail` | Required; must not duplicate another user's; can also be used to log in, and determines the avatar |

Click **Update my profile** (更新我的档案) to save; the message "Your profile has been updated" (您的档案已经更新) appears. The username cannot be changed here.

:::caution Do not clear the nickname
The UI hint says "If you leave this empty, the username will be used by default" (如果你将此项留空, 将默认使用用户名), but in 1.3.0, if you clear an **existing** nickname and save, the nickname becomes an empty value and no name is shown in the admin panel or on the front end any more; see [Users, roles and registration](./users.md) for details. Just fill in the nickname again when needed.
:::

## Writing settings {#writing}

Only **contributors and higher roles** have this block (subscribers cannot write posts, so they do not see it). These settings are saved per user in the `options` table (the `user` column is your user ID); users who have never set them use the defaults from installation.

| UI name | Option name | Default | Description |
|---|---|---|---|
| Use Markdown syntax to edit and parse content (使用 Markdown 语法编辑和解析内容) | `markdown` | On | When on, the editor has a Markdown toolbar and preview, and new posts are saved as Markdown. "Enabling this does not affect content that was not previously edited with Markdown syntax" (此功能开启不会影响以前没有使用 Markdown 语法编辑的内容) |
| Use Markdown syntax in the XMLRPC interface (在 XMLRPC 接口中使用 Markdown 语法) | `xmlrpcMarkdown` | Off | When posting from an offline client with this on, content is saved as Markdown, and reading returns the Markdown source instead of the converted HTML. Turn it on only if the client itself supports writing in Markdown, see [Feeds and offline clients](./feeds-xmlrpc.md#markdown) |
| Auto save (自动保存) | `autoSave` | Off | When on, the editor automatically saves a draft or revision 3 seconds after you stop typing, see [Writing posts](./posts.md#drafts) |
| Allow by default (默认允许) | `defaultAllowComment`, `defaultAllowPing`, `defaultAllowFeed` | All checked | Three checkboxes, "Can be commented on" (可以被评论), "Can be trackbacked" (可以被引用) and "Appear in feeds" (出现在聚合中), decide whether "Allow comments", "Allow trackbacks" and "Allow in feeds" are checked by default for new posts; posts published via XML-RPC also use these settings |

Click **Save settings** (保存设置) to apply. These settings apply per user: for example, after a user turns on `xmlrpcMarkdown`, posts published with their account via `metaWeblog.newPost` carry the `<!--markdown-->` marker in the content, and other users are not affected; items not checked under "Allow by default" are also turned off for posts published via XML-RPC (for example, after unchecking "Can be trackbacked", `allowPing` is 0).

## Changing your password {#change-password}

In the **Change password** (密码修改) block, fill in "Password" (用户密码) and "Confirm password" (用户密码确认) (at least 6 characters, both identical), then click **Update password** (更新密码); the message "Password changed successfully" (密码已经成功修改) appears. "We strongly recommend changing your default password" (强烈建议更改你的默认密码) on the welcome page links here.

A forgotten password cannot be recovered here, see [Users, roles and registration](./users.md).

## Personal plugin settings {#plugins}

Besides the site-wide settings an administrator configures on the **Plugins** (插件) page, some plugins also offer **settings for each user** (the plugin implements `personalConfig()`). Once such a plugin is enabled, a block titled with the plugin name appears at the bottom of the Profile page; fill it in and click that block's **Save settings**, and the values apply only to you.

- The block is shown only to users who have the role the plugin requires. When the plugin specifies nothing, every user who can log in (subscriber and higher) sees it; when the plugin requires contributor, the block does not appear on a subscriber's Profile page.
- When the plugin is disabled, the block disappears.
- For what each field means, see the plugin's own documentation.
