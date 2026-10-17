---
title: Profile
sidebar_position: 10
translation: machine
source_commit: c9097eed4a7142f9eabc1bca041ef819d6a64bbd
---

# Profile

Every user who can log in to the admin panel has **Dashboard → Profile** (`admin/profile.php`); you can also open it by clicking your own name in the top right corner. What you change here is only **your own** profile and preferences; other users are not affected.

The left side of the page shows your avatar, nickname, "You have N posts, and N comments about you in N categories" and your last login time; the right side contains the following blocks, from top to bottom.

## Avatar {#avatar}

Typecho does not store avatar images. Avatars in the admin panel and on the front end all come from [Gravatar](https://gravatar.com/), looked up by your **email address**:

- To change your avatar, register on gravatar.com with the same email and upload an avatar; clicking the avatar on the Profile page (tooltip "Change avatar on Gravatar") also opens Gravatar;
- If you change your email address, the avatar changes with it; when the email has no avatar on Gravatar, the default grey silhouette is shown;
- The avatar URL is the Gravatar URL plus the MD5 of the email (trimmed and lowercased); when the site uses HTTPS, `https://secure.gravatar.com` is used, otherwise `http://www.gravatar.com`;
- When Gravatar is slow to reach from mainland China, you can switch to a mirror with the `__TYPECHO_GRAVATAR_PREFIX__` constant, see [Configuration file](../operations/config-file.md).

## Personal information

| UI name | Field | Description |
|---|---|---|
| Nickname | `screenName` | The name shown on the front end; must not duplicate another user's nickname |
| Homepage URL | `url` | Must start with `http://` or `https://`; since 1.3, internationalized domain names (such as Chinese ones) are supported |
| Email address * | `mail` | Required; must not duplicate another user's; can also be used to log in, and determines the avatar |

Click **Update my profile** to save; the message "Your profile has been updated" appears. The username cannot be changed here.

:::caution Do not clear the nickname
The UI hint says "If you leave this empty, the username will be used by default", but in 1.3.0, if you clear an **existing** nickname and save, the nickname becomes an empty value and no name is shown in the admin panel or on the front end any more; see [Users, roles and registration](./users.md) for details. Just fill in the nickname again when needed.
:::

## Writing settings {#writing}

Only **contributors and higher roles** have this block (subscribers cannot write posts, so they do not see it). These settings are saved per user in the `options` table (the `user` column is your user ID); users who have never set them use the defaults from installation.

| UI name | Option name | Default | Description |
|---|---|---|---|
| Use Markdown syntax to edit and parse content | `markdown` | On | When on, the editor has a Markdown toolbar and preview, and new posts are saved as Markdown. "Enabling this does not affect content that was not previously edited with Markdown syntax" |
| Use Markdown syntax in the XMLRPC interface | `xmlrpcMarkdown` | Off | When posting from an offline client with this on, content is saved as Markdown, and reading returns the Markdown source instead of the converted HTML. It needs `markdown` above to be on as well, otherwise the content is not saved as Markdown. Turn it on only if the client itself supports writing in Markdown, see [Feeds and offline clients](./feeds-xmlrpc.md#markdown) |
| Auto save | `autoSave` | Off | When on, the editor automatically saves a draft or revision 3 seconds after you stop typing, see [Writing posts](./posts.md#drafts) |
| Allow by default | `defaultAllowComment`, `defaultAllowPing`, `defaultAllowFeed` | All checked | Three checkboxes (allow comments, allow trackbacks and pingbacks, appear in feeds; the English interface labels them "Commentable", "Citable" and "Shown in aggregation.") decide whether "Allow comments", "Allow trackbacks" and "Allow in feeds" are checked by default for new posts; for posts published via XML-RPC, the comment and trackback values are used only when the client does not specify them, and the feed value is always used |

Click **Save settings** to apply. These settings apply per user: for example, after a user turns on `markdown` and `xmlrpcMarkdown`, posts published with their account via `metaWeblog.newPost` carry the `<!--markdown-->` marker in the content, and other users are not affected; when the client does not specify the comment and trackback settings, items not checked under "Allow by default" are also turned off for posts published via XML-RPC (for example, after unchecking "Citable", when the client does not send `mt_allow_pings`, `allowPing` is 0).

## Changing your password {#change-password}

In the **Change password** block, fill in "Password" and "Confirm password" (at least 6 characters, both identical), then click **Update password**; the message "Password changed successfully" appears. "We strongly recommend changing your default password" on the welcome page links here.

A forgotten password cannot be recovered here, see [Users, roles and registration](./users.md).

## Personal plugin settings {#plugins}

Besides the site-wide settings an administrator configures on the **Plugins** page, some plugins also offer **settings for each user** (the plugin implements `personalConfig()`). Once such a plugin is enabled, a block titled with the plugin name appears at the bottom of the Profile page; fill it in and click that block's **Save settings**, and the values apply only to you.

- The block is shown only to users who have the role the plugin requires. When the plugin specifies nothing, every user who can log in (subscriber and higher) sees it; when the plugin requires contributor, the block does not appear on a subscriber's Profile page.
- When the plugin is disabled, the block disappears.
- For what each field means, see the plugin's own documentation.
