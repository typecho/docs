---
title: Visibility and password protection
sidebar_position: 7
translation: machine
source_commit: 5cf852b2f6ae6c6a96b12a34d8bd77f83e99bfa7
---

# Visibility and password protection

**Visibility** (`visibility`) in the post editor's **Advanced options** decides who can see a post on the front end. **Only editors and higher roles** can see and change this option; posts published by contributors are always "Pending review". Pages have only two visibility values: "Public" and "Hidden".

## The five visibility values

| UI name | Saved status | Meaning |
|---|---|---|
| Public | `publish` | Visible to everyone |
| Hidden | `hidden` | Does not appear in any list, but anyone who knows the URL can open it |
| Password protected | `publish`, with a **Content password** (`password`) set | Appears in lists, but the title and content are shown only after entering the password |
| Private | `private` | Visible only to the author, when logged in |
| Pending review | `waiting` | Nobody can see it on the front end; it waits for an editor or administrator to review it |

When you choose "Password protected", a **Content password** input box appears below.

Two other kinds of posts are also invisible on the front end: **drafts** (never published; see [Writing posts](./posts.md#drafts)) and **scheduled posts** (public, but with a publish date in the future; see [Writing posts](./posts.md#scheduled)).

## How they behave on the front end

In the table below, "lists" means post list pages such as the home page, category, tag, author and date archives:

| | Lists | Search results | Feeds | Opening the post URL | Previous / next post | Counted in category and tag post counts |
|---|---|---|---|---|---|---|
| Public | Shown | Shown | Shown | 200 | Shown | Yes |
| Hidden | Not shown | Not shown | Not shown | 200, anyone can view | Not shown | No |
| Password protected | Shown as "This content is password protected" | Not shown | **Not shown** | 403, shows the password form | Not shown | Yes |
| Private | Shown only to the author | Shown only to the author | Not shown when logged out; shown to the author when logged in | 200 for the author, 404 for everyone else | Not shown | No |
| Pending review | Not shown | Not shown | Not shown | 404, for the author as well | Not shown | No |
| Scheduled (before the time) | Not shown | Not shown | Not shown | 404, for the author as well | Not shown | Yes (counted in advance) |

A few notes:

- **Private depends on the author, not on the role**: other administrators and editors also get a 404 when they open someone else's private post. The author must log in to the admin panel first, then visit the front end.
- **Posts pending review** can be previewed in the admin panel with the editor's **Preview post**.
- Hidden does not mean secret: once the URL of a hidden post is shared, anyone can open it. When you need to restrict access, use "Password protected" or "Private".
- Visibility and content passwords **do not protect uploaded attachments**: attachment pages and file URLs do not check the status of the post they belong to, so anyone who knows the URL can open them. See [Attachments and file management](./attachments.md#attachment-page).
- After a post is made private or hidden, its approved comments still appear in the site-wide comment feed. See [Comment feed](./feeds-xmlrpc.md#comment-feed).

## Password protection details {#password}

When a visitor opens a password-protected post:

- the page returns HTTP 403, the title is shown as "This content is password protected", and in place of the content there is a "Please enter the password to access" form;
- entering a wrong password returns 403 and "Sorry, the password you entered is incorrect";
- after the correct password is entered, the browser stores a session cookie named `protectPassword_<post ID>`. Until the browser is closed, the visitor can open this post again without re-entering the password, and list pages also show the post's real title and content;
- until the post is unlocked, the visitor cannot comment on it (the comment form is not shown).

The post's author and all editors and administrators (when logged in) can see the content **without entering the password**. A contributor visiting someone else's password-protected post still has to enter the password.

Password-protected posts **never appear in feeds**, even after the password has been entered; they do not appear in search results either (except for the author when logged in).

:::note
The unlocking cookie stores the password in plain text, so do not use a content password that is the same as any account password.
:::

## Bulk changes

Editors and administrators can check posts in **Manage → Posts** and use **Mark as public / pending review / hidden / private** in the **Selected items** menu to change them in bulk (there is no option to set a password in bulk). Marking also updates the status of the post's revision, and increases or decreases the post counts of its categories and tags accordingly. Reviewing a contributor's submission means marking the "Pending review" post as public; the default "Mine" view does not show other people's submissions, so switch to "All" first. See [Writing posts](./posts.md#publish) for the steps.
