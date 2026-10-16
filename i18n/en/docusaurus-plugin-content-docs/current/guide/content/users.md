---
title: Users, roles and registration
sidebar_position: 9
translation: machine
source_commit: b560bd74333d461677cbf639d87213a65dd5fde6
---

# Users, roles and registration

Typecho uses "user groups" (roles) to control what each account can do. Users are managed in **Manage → Users** (`admin/manage-users.php`), which **only administrators** can access.

## The five roles

| Role | User group value | In one sentence |
|---|---|---|
| Administrator | `administrator` | Has all permissions, including settings, plugins, appearance, users and backup |
| Editor | `editor` | Manages all content on the site: all posts, pages, categories, tags, files and comments |
| Contributor | `contributor` | Can write posts, but they must be reviewed before they are published; can manage only their own posts and the comments on them |
| Subscriber | `subscriber` | Can only log in to the admin panel and edit their own profile; the default role for new users when registration is open |
| Visitor | `visitor` | A visitor who is not logged in. Cannot be assigned to an account in the admin panel, see [below](#visitor) |

Roles are cumulative: a higher role has all the permissions of the roles below it.

## Permission table {#permission-table}

The link "See here for the detailed permission table" below the user group on the **Edit user** page of the admin panel points to this section. ✓ means allowed, ✗ means not allowed.

| Action | Administrator | Editor | Contributor | Subscriber | Visitor |
|---|---|---|---|---|---|
| Browse the front end, post comments (when the comment settings allow) | ✓ | ✓ | ✓ | ✓ | ✓ |
| Log in to the admin panel, view the dashboard | ✓ | ✓ | ✓ | ✓ | ✗ |
| Edit their own profile and password (Profile) | ✓ | ✓ | ✓ | ✓ | ✗ |
| "Writing settings" in Profile | ✓ | ✓ | ✓ | ✗ | ✗ |
| Write posts, save drafts | ✓ | ✓ | ✓ | ✗ | ✗ |
| Publish posts directly, set "Visibility" (hidden, private, password protected and so on) | ✓ | ✓ | ✗ always pending review | ✗ | ✗ |
| Edit and delete their own posts | ✓ | ✓ | ✓ back to pending review after republishing | ✗ | ✗ |
| Edit and delete other people's posts, review submissions | ✓ | ✓ | ✗ | ✗ | ✗ |
| Choose categories and enter new tags when writing posts | ✓ | ✓ | ✓ | ✗ | ✗ |
| Manage categories and tags | ✓ | ✓ | ✗ | ✗ | ✗ |
| Create, edit and delete pages | ✓ | ✓ | ✗ | ✗ | ✗ |
| Upload attachments; edit, replace and delete their own attachments | ✓ | ✓ | ✓ | ✗ | ✗ |
| Manage all files ("Manage → Files"), clean up unattached files | ✓ | ✓ | ✗ | ✗ | ✗ |
| Manage comments on their own posts (moderate, reply, edit, delete) | ✓ | ✓ | ✓ | ✗ | ✗ |
| Manage all comments on the site | ✓ | ✓ | ✗ | ✗ | ✗ |
| View other people's password-protected posts without the password | ✓ | ✓ | ✗ | ✗ | ✗ |
| View other people's private posts | ✗ | ✗ | ✗ | ✗ | ✗ |
| Publish and manage posts with an XML-RPC client | ✓ | ✓ | ✓ only their own posts | ✗ | ✗ |
| Manage pages, categories and the author list via XML-RPC | ✓ | ✓ | ✗ | ✗ | ✗ |
| Manage users | ✓ | ✗ | ✗ | ✗ | ✗ |
| System settings (General, Discussion, Reading, Permalink) | ✓ | ✗ | ✗ | ✗ | ✗ |
| Plugins, appearance, editing theme files, backup, upgrade | ✓ | ✗ | ✗ | ✗ | ✗ |

Additional notes:

- A private post can be seen **only by its author**, regardless of role; administrators cannot see other people's private posts either, see [Visibility and password protection](./visibility.md).
- A plugin's personal settings (at the bottom of the Profile page) declare their own minimum role, subscriber by default, see [Profile](./profile.md#plugins).
- Admin pages added by plugins also have a minimum role set by the plugin, administrator by default.
- For the entry points of each page and which menus are shown, see [Admin overview](./admin-overview.md).

## Adding and editing users

In **Manage → Users**, click **Add** (`user.php`):

| UI name | Field | Description |
|---|---|---|
| Username * | `name` | The name used to log in; must not duplicate an existing user; cannot be changed after creation |
| Email address * | `mail` | The main contact; must not be duplicated; can also be used to log in; the avatar is fetched from Gravatar by this email |
| Nickname | `screenName` | The name shown on the front end; the username is used when left empty |
| Password * / Confirm password * | `password` / `confirm` | At least 6 characters; when editing an existing user, leave empty to keep it unchanged |
| Homepage URL | `url` | Must start with `http://` or `https://` |
| User group | `group` | A drop-down: Subscriber (the default), Contributor, Editor, Administrator |

:::caution Do not clear the "Nickname" when editing a user
According to the UI hint, an empty nickname should fall back to the username. But in 1.3.0, if you **clear an existing nickname** in "Edit user" or "Profile" and save, the nickname becomes an empty value (PHP also logs an `Undefined array key "name"` warning), and no name is shown in the admin panel or on the front end; leaving it empty when adding a new user does not have this problem. To recover, just fill in the nickname again.
:::

### The user group drop-down has only four options {#visitor}

The user group drop-down offers only four options, **Subscriber, Contributor, Editor and Administrator**. **Visitor (visitor) cannot be assigned manually**: it represents a visitor who is not logged in, and is not a role for accounts.

If an account ends up in the `visitor` group, for example because the database was edited directly, it can still log in, but every admin page (including the dashboard and Profile) returns 403; on the "Edit user" page its user group is shown as the first option, "Subscriber", and saving it directly turns it into a subscriber.

## User list and deletion

The list shows each user's post count, username, nickname, email and user group, and can be filtered by keyword. Check users and delete them from the **Selected items** menu (confirm "Are you sure you want to delete these users?"):

- **The first user** (the account with the smallest ID, usually the administrator created during installation) and **yourself** are not deleted; they are skipped even if checked;
- Deleting a user **does not delete or transfer their posts and comments**: the posts remain, and the author column of the admin post list becomes blank. If you want to keep the byline, hand the posts over to someone else before deleting, or change the account to a subscriber instead of deleting it.

## Open registration {#register}

When "Allow registration" (`allowRegister`, default "Not allowed") in **Settings → General** is set to "Allowed", a "Register" link appears at the bottom of the login page, and visitors can register themselves at `admin/register.php`:

- The registration form has only two fields, **Username** and **Email**. The username is 2 to 32 characters, the email at most 64 characters, and neither may duplicate an existing user.
- After registering, the user is logged in automatically and lands on the welcome page, which shows a randomly generated 7-character password once, for example "用户 newbie 已经成功注册, 密码为 q1wVUkl" (user newbie has registered successfully, the password is q1wVUkl). Remind users to note the password and change it in Profile as soon as possible.
- The role of new users is always **Subscriber**, with no write permission; an administrator must promote them manually.
- Typecho sends no email and does not verify the email address during registration.

## Login

- The "Username or email" field in the login box accepts either.
- An account can stay logged in at only one place at a time; a new login invalidates the previous one.
- When the password is wrong, the login request deliberately waits about 3 seconds before returning, but there is no account lockout mechanism, see [Security hardening](../operations/security.md).
- If a password is forgotten, the admin panel has no "Forgot password" feature. An administrator can set a new password for other users in "Edit user"; if the administrator's own password is lost, see [Installation](../getting-started/installation.mdx#安装后丢失管理员密码).
