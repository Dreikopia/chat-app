# Product Requirements Document: DM Chat App (v1)

| Field                                     | Value                                                                                                 |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Version                                   | 1.0 (Draft for review)                                                                                |
| Date                                      | 2026-11-16                                                                                            |
| Project                                   | DM Chat App (working title), a one-week learning project on the RILT stack                            |
| Stack (assumed, repository not inspected) | Laravel, Inertia.js, React (JSX), Tailwind CSS, shadcn/ui, MySQL, Laravel Reverb + Laravel Echo, Pest |
| Companion file                            | `DESIGN.md` (visual design, exact copy, component specs)                                              |
| Status                                    | Draft                                                                                                 |

Tags used in this document: `[REQUIRED]` must be built as written. `[RECOMMENDED]` suggested approach. `[ASSUMPTION]` believed true, needs confirming. `[OPEN QUESTION]` a decision that is still yours.

## Open Questions Summary

| ID   | Question                                                                                                                   | Why it matters                                         | Suggested default                                   |
| ---- | -------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ | --------------------------------------------------- |
| OQ-1 | Can a sender delete a message at any time, or only within a window (for example 15 minutes)?                               | Changes the delete rule and adds a time check.         | Any time.                                           |
| OQ-2 | After deleting, show a "This message was deleted" placeholder, or remove the message completely?                           | Changes the UI, the live update, and the unread count. | Placeholder, so the conversation still makes sense. |
| OQ-3 | Is the simple schema (one `messages` table with sender and recipient) acceptable, given rooms will force a refactor in v2? | Decides the v2 refactor work.                          | Yes. The refactor is intended practice.             |
| OQ-4 | Should a user's bio be visible to other people (in the chat header)?                                                       | Decides FR-17 and the privacy of the bio field.        | Yes, shown in the chat header.                      |

---

## 1. Feature Overview

- **Feature name:** DM Chat App (v1: direct messages only)
- **Description:** A minimalist web app where logged-in users find each other by username, start a one-to-one chat, and send messages that appear instantly on the other person's screen. Users can edit their profile (name, username, bio, avatar), delete their own messages, and see unread counts.
- **Purpose:** One small app that exercises form submission, rate limiting, authentication and authorization, real-time updates, and React fundamentals on the RILT stack, and that leaves room for a deliberate refactor when rooms are added in v2.
- **Problem statement:** The builder needs a realistic project covering forms, rate limits, real-time, and authorization that can be finished in one week at 3 to 4 hours a day. Inside the app, two people need a private, instant way to talk without refreshing the page.

## 2. Goals and Objectives

- **Primary goals**
    1. Two logged-in users can hold a private conversation in real time.
    2. Every form in the app (profile details, avatar, new chat, message, delete confirm) shows server errors correctly and keeps the user's input.
    3. Five specific actions are protected by rate limits that the user can understand when they are blocked.
    4. A deleted message is gone for both people, and its text is never sent to the browser again.
- **Expected outcomes:** A working app with 19 numbered acceptance criteria passing, at least 8 automated tests, and a clean small codebase ready to refactor for rooms.
- **Success metrics (verifiable)**
    - A message appears on the other person's screen in 1 second or less on a local machine (NFR-1).
    - 0 responses or broadcasts contain the text of a deleted message (BR-2, AC-16).
    - Each of the 5 rate limits blocks the request after its stated count and shows its message (AC-5, AC-8, AC-10, AC-19, AC-32).
    - 5 of 5 forms show field-level server errors without clearing the other fields.

## 3. User Roles and Permissions

There is one role: a logged-in user. Guests can only reach the register and login pages.

| Action                                         | Guest | Logged-in user                        |
| ---------------------------------------------- | ----- | ------------------------------------- |
| Register, log in                               | ✓     | ✗ (redirected to `/chat`)             |
| Open chat pages                                | ✗     | ✓                                     |
| Search users by username                       | ✗     | ✓                                     |
| Start a chat with another user                 | ✗     | ✓                                     |
| Read messages in a chat                        | ✗     | ✓, only chats they are part of        |
| Send a message                                 | ✗     | ✓                                     |
| Delete a message                               | ✗     | ✓, only their own messages            |
| Edit own profile and avatar                    | ✗     | ✓                                     |
| See another user's name, username, avatar, bio | ✗     | ✓ (search results and chat header)    |
| Listen to a real-time channel                  | ✗     | ✓, only their own `user.{id}` channel |

- **Authorization restrictions**
    - A user can only ever see messages where they are the sender or the recipient (BR-1).
    - A user can only delete messages they sent (BR-8). Enforced by a policy on the server, not only by hiding the menu.
    - A user can only listen to their own private channel (section 10).

## 4. User Stories

- **US-1:** As a visitor, I want to register and log in, so that I have an account to chat with.
- **US-2:** As a user, I want to change my name, username, and bio, so that people recognise me.
- **US-3:** As a user, I want to upload an avatar, so that my chats feel personal.
- **US-4:** As a user, I want to find someone by username, so that I can start a chat.
- **US-5:** As a user, I want to send a message and see it appear for the other person immediately, so that the chat feels live.
- **US-6:** As a user, I want to see how many unread messages each chat has, so that I know where to reply first.
- **US-7:** As a user, I want to delete a message I sent, so that I can remove a mistake.
- **US-8:** As a user, I want a clear message when I am sending too many requests, so that I know when to try again.
- **US-9:** As a user, I want to see who is online and who is typing, so that I know when someone is there. _(stretch)_

## 5. Functional Requirements

| ID    | Requirement                                                                                                                                                                                                                                                                                                                                                                                                     | Priority |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| FR-1  | The profile details form shall let a user change name, username, and bio using the rules in section 12. On a validation failure it shall show each error under its field and keep every typed value.                                                                                                                                                                                                            | Must     |
| FR-2  | The avatar form shall be separate from the details form. It shall show a preview of the chosen image before upload, accept one JPG, PNG, or WebP image up to 2 MB, and replace the previous avatar (BR-9).                                                                                                                                                                                                      | Must     |
| FR-3  | After a successful save from FR-1 or FR-2, the new name and avatar shall appear in the sidebar footer and the chat header without a manual page refresh.                                                                                                                                                                                                                                                        | Must     |
| FR-4  | User search shall return up to 10 users whose username starts with the typed text (case-insensitive), excluding the current user. It shall run only for 2 or more characters and shall wait 300 ms after the last keystroke before sending the request. Each result shows avatar, name, and @username.                                                                                                          | Must     |
| FR-5  | The "New chat" dialog shall contain a Username field (with the FR-4 results), a Message field, and a "Start chat" button. On success the user lands in the chat with the message shown. If a chat with that user already exists, the message is sent into it and does not count toward RL-1.                                                                                                                    | Must     |
| FR-6  | The sidebar shall list every person the user has exchanged at least one message with, ordered by the time of the latest message (deleted messages included), newest first. Each row shows avatar, name, and the unread badge.                                                                                                                                                                                   | Must     |
| FR-7  | The message form shall send plain text of 1 to 1000 characters that is not only whitespace. On success the box clears and keeps focus. On failure the box keeps its text and shows "Message not sent. Try again." Enter sends; Shift+Enter inserts a new line.                                                                                                                                                  | Must     |
| FR-8  | Opening a chat shall show the latest 50 messages, oldest at the top. The user's own messages are on the right and the other person's on the left. Messages from the same person within 5 minutes form a run, and the time of the run's last message is shown below it in the browser's local time. A date line ("Today", "Yesterday", or a date such as "Mon 9 Nov") appears where the day changes.             | Must     |
| FR-9  | A message sent by the other person shall appear within 1 second without a refresh. If the list is within 80 px of the bottom it scrolls to the bottom; otherwise a "New messages" button appears and scrolls to the bottom when clicked. If that chat is open, the message is marked read immediately (FR-14).                                                                                                  | Must     |
| FR-10 | Each of the user's own, non-deleted messages shall have a "Message options" menu with one item, "Delete". Other people's messages and deleted messages have no menu.                                                                                                                                                                                                                                            | Must     |
| FR-11 | Choosing "Delete" shall open a confirm dialog titled "Delete this message?" with the text "This can't be undone." and the buttons "Cancel" and "Delete".                                                                                                                                                                                                                                                        | Must     |
| FR-12 | After confirming, the message shall be replaced in place by "This message was deleted" for both people. The other person's screen shall update without a refresh, and their unread badge shall adjust (BR-3).                                                                                                                                                                                                   | Must     |
| FR-13 | A delete request for a message the user owns that is already deleted shall succeed and change nothing.                                                                                                                                                                                                                                                                                                          | Should   |
| FR-14 | The sidebar shall show an unread badge per chat (count rule in BR-3). It shows "9+" above 9 and is hidden at 0. It rises instantly when a message arrives in a chat that is not open, clears when the user opens that chat, and stays cleared after a refresh.                                                                                                                                                  | Must     |
| FR-15 | When a rate limit from section 13 blocks a request, the form shall show that limit's message (section 12) with the wait time, keep all typed values, and disable its submit button until the wait is over.                                                                                                                                                                                                      | Must     |
| FR-16 | Visitors shall be able to register (name, email, password), log in, and log out. Registration shall generate a username from the name: keep only a to z and 0 to 9 (spaces become underscores), cut to 15 characters, add an underscore and 4 random digits, and retry until unique. If nothing is left after cleaning, use "user" as the base. Guests who open any chat page are redirected to the login page. | Must     |
| FR-17 | The chat header shall show the other person's avatar, name, @username, and bio (one line, truncated, shown only if set).                                                                                                                                                                                                                                                                                        | Should   |
| FR-18 | A dot shall appear next to online people in the sidebar and chat header and disappear within 10 seconds of them closing the app.                                                                                                                                                                                                                                                                                | Could    |
| FR-19 | "{Name} is typing…" shall appear in the chat header while the other person types. It disappears 3 seconds after the last signal or when their message arrives. The sender's signal is sent at most once per second.                                                                                                                                                                                             | Could    |

### Non-functional requirements

| ID    | Requirement                                                                                                     |
| ----- | --------------------------------------------------------------------------------------------------------------- |
| NFR-1 | A sent message reaches the other person's screen in 1 second or less on a local machine.                        |
| NFR-2 | Every screen specifies: loading, empty, failed, and disconnected states (section 8).                            |
| NFR-3 | The app works at a 320 px wide screen with no horizontal scrolling.                                             |
| NFR-4 | At least 8 automated tests exist (section 15).                                                                  |
| NFR-5 | Every control works with the keyboard alone, shows a visible focus ring, and text meets a 4.5:1 contrast ratio. |

## 6. Business Rules

| ID   | Rule                                                                                                                                                                                                                                                                |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR-1 | Only the sender and the recipient can see a message. Opening `/chat/{user}` shows only the messages between the current user and that user.                                                                                                                         |
| BR-2 | Deleting is soft: the row stays with `deleted_at` set. `[REQUIRED]` The server shall never send the body of a deleted message to the browser (page data or broadcast). `[RECOMMENDED]` Also set `body` to null at deletion so the text is not kept in the database. |
| BR-3 | Unread count for a chat = messages sent to the current user by that person where `read_at` is empty and `deleted_at` is empty. Deleting an unread message lowers the recipient's count.                                                                             |
| BR-4 | Messages are plain text. HTML typed by a user is shown as text, never run.                                                                                                                                                                                          |
| BR-5 | Usernames are unique, 3 to 20 characters, lowercase letters, numbers, and underscores only.                                                                                                                                                                         |
| BR-6 | A user cannot start a chat with themselves.                                                                                                                                                                                                                         |
| BR-7 | Sending messages inside an existing chat is not rate limited in v1. Only the actions in section 13 are limited.                                                                                                                                                     |
| BR-8 | Only the sender of a message can delete it.                                                                                                                                                                                                                         |
| BR-9 | Saving a new avatar deletes the previous avatar file. A failed upload leaves the current avatar untouched.                                                                                                                                                          |

**Message state transitions**

| From           | To       | Allowed by  | Condition                                               |
| -------------- | -------- | ----------- | ------------------------------------------------------- |
| Unread         | Read     | Recipient   | Opens the chat, or has it open when the message arrives |
| Unread or Read | Deleted  | Sender only | Not already deleted                                     |
| Deleted        | anything | Nobody      | Deleted is final                                        |

## 7. User Workflow

**Workflow A: Start a chat**

1. User clicks "New chat" in the sidebar. The dialog opens with focus in the Username field.
2. User types at least 2 characters. After 300 ms the system shows matching users.
3. User picks a person and writes a first message, then clicks "Start chat".
4. System creates the first message, closes the dialog, and opens the chat.

**Workflow B: Send and receive**

1. User types in the composer and presses Enter.
2. System saves the message and shows it on the right side of the sender's screen.
3. System broadcasts to the recipient's private channel. The recipient's screen shows it on the left, or raises that chat's unread badge if another chat is open.

**Workflow C: Delete a message**

1. User opens "Message options" on their own message and chooses "Delete".
2. System shows the confirm dialog. User clicks "Delete".
3. System soft-deletes the message and broadcasts. Both screens show "This message was deleted". The recipient's badge drops if the message was unread.

**Workflow D: Change avatar (with a rate limit)**

1. User chooses an image. The preview shows.
2. User clicks "Save photo". System validates, stores the file, removes the old one, and shows "Photo updated."
3. On the 4th upload within an hour, the system answers 429. The form shows the wait message, keeps the preview, and disables "Save photo" until the wait ends.

**Failure scenarios**

- Websocket disconnected: a "Reconnecting…" banner shows. Messages already on screen stay. Sending still works over HTTP; the recipient sees the message when they reload or reconnect.
- Send fails (server error or network): the composer keeps its text and shows "Message not sent. Try again." No message is saved unless the server confirmed it.
- Upload fails validation: the field error shows, the current avatar is unchanged, no file is stored.
- Delete is rejected (403 or network error): the message stays as it was and the dialog shows "Something went wrong. Try again."

## 8. UI/UX Requirements

Visual design, tokens, wireframes, and the exact copy deck are in `DESIGN.md`. This section lists what must exist.

- **Pages and components:** Chat page with sidebar and conversation pane; chat empty page; Profile page with two forms; "New chat" dialog; delete confirm dialog; message options menu; toast messages.
- **Forms and dialogs:**
    - Details form: Name, Username, Bio (with a 0/160 counter), "Save changes".
    - Avatar form: "Choose image", preview, "Save photo".
    - New chat dialog: Username (with results), Message, "Cancel", "Start chat".
    - Composer: one multi-line field and a "Send" button.
- **Tables and data presentation:** No tables. The sidebar is a list ordered by latest message (FR-6). Search results show at most 10 rows.
- **Loading states:** Chat page and search show the text "Loading…" in muted color. No spinners or shimmer.
- **Empty states:** Sidebar: "No chats yet. Search for someone to start." Chat with no messages: "No messages yet. Say hi." Search with no match: "No one found."
- **Error states:** Field-level errors under the field. Form-level errors (send failed, 429) directly under the submit control. Page-level failure: "Something went wrong. Try again."
- **Success feedback:** Toast "Profile saved." and "Photo updated." Starting a chat lands in the chat. Deleting shows the placeholder, no toast.
- **Responsive behavior:** Below 768 px, one column at a time (chat list, then chat with a back control). At 768 px and above, sidebar and conversation side by side.
- **Accessibility:** Full keyboard use, focus moves into dialogs and returns to the trigger on close, error text is announced by screen readers, the message list is a live region that announces new messages politely, deleted placeholders are announced as text, contrast 4.5:1 or better.

## 9. Database Requirements

All changes are **proposed** (new app, nothing inspected).

**Table: `users`** (modified; the Breeze base table is assumed, see AS-2)

| Column      | Type         | Null | Default | Notes                                     |
| ----------- | ------------ | ---- | ------- | ----------------------------------------- |
| username    | varchar(20)  | no   | none    | Unique, lowercase only (BR-5)             |
| bio         | varchar(160) | yes  | null    |                                           |
| avatar_path | varchar(255) | yes  | null    | Path on the public disk, random file name |

**Table: `messages`** (new)

| Column                 | Type            | Null | Default | Notes                                      |
| ---------------------- | --------------- | ---- | ------- | ------------------------------------------ |
| id                     | bigint unsigned | no   | auto    | PK                                         |
| sender_id              | bigint unsigned | no   | none    | FK to `users.id`                           |
| recipient_id           | bigint unsigned | no   | none    | FK to `users.id`                           |
| body                   | varchar(1000)   | yes  | none    | Null after deletion (BR-2)                 |
| read_at                | timestamp       | yes  | null    | Set when the recipient reads it            |
| deleted_at             | timestamp       | yes  | null    | Set when the sender deletes it             |
| created_at, updated_at | timestamp       | no   |         | Stored in UTC, shown in browser local time |

- **Relationships:** A message belongs to a sender and to a recipient (both `User`). A user has many sent and many received messages.
- **Foreign keys and on-delete behavior:** Both FKs use restrict on delete, so a user row cannot be removed while messages exist. Account deletion is out of scope (AS-6).
- **Constraints:** `username` unique. `[RECOMMENDED]` CHECK `sender_id <> recipient_id` (the app also validates BR-6).
- **Indexes**
    - `messages_pair_idx (sender_id, recipient_id, id)`: loads "messages I sent to X".
    - `messages_inbox_idx (recipient_id, sender_id, read_at, id)`: loads "messages X sent to me", unread counts, and the sidebar list.
    - `users.username` unique index: serves username search by prefix.
- **Migration considerations:** In a new app, add the columns directly. If this is added to an existing app, add `username` as nullable, backfill every existing user with the FR-16 generator, then make it required and unique. Rollback drops `messages` first, then the three `users` columns. Avatars need `php artisan storage:link` once per environment.

## 10. Backend Requirements

**Routes**

| Method | URI                     | Name             | Controller@action             | Middleware                             |
| ------ | ----------------------- | ---------------- | ----------------------------- | -------------------------------------- |
| GET    | `/chat`                 | chat.index       | ChatController@index          | auth                                   |
| GET    | `/chat/{user}`          | chat.show        | ChatController@show           | auth                                   |
| GET    | `/users/search`         | users.search     | UserSearchController@index    | auth, throttle `user-search` (RL-3)    |
| POST   | `/dms`                  | dms.store        | DirectMessageController@store | auth                                   |
| POST   | `/chat/{user}/messages` | messages.store   | MessageController@store       | auth                                   |
| DELETE | `/messages/{message}`   | messages.destroy | MessageController@destroy     | auth                                   |
| POST   | `/chat/{user}/read`     | chat.read        | ChatReadController@store      | auth                                   |
| PATCH  | `/profile`              | profile.update   | ProfileController@update      | auth, throttle `profile-update` (RL-5) |
| POST   | `/profile/avatar`       | profile.avatar   | AvatarController@store        | auth, throttle `avatar-upload` (RL-4)  |

`[REQUIRED]` RL-1 is applied inside `DirectMessageController@store`, and only when no earlier message exists between the two users. A plain route throttle would also block messages into existing chats, which BR-7 forbids.

- **Controllers:** As listed above. `[RECOMMENDED]` Keep logic in the controllers for v1. Extracting services is the planned v2 refactor.
- **Form Requests:** `StartDmRequest`, `StoreMessageRequest`, `UpdateProfileRequest`, `UpdateAvatarRequest`. Rules in section 12.
- **Models and relationships:** `User`, `Message` (relationships in section 9). Use `$fillable` lists (no `$guarded = []`).
- **Services / business logic:** None in v1.
- **Policies and authorization:** `MessagePolicy@delete` returns true only when `message.sender_id` equals the current user's id.
- **Real-time**
    - Events: `MessageSent` (payload: id, sender_id, body, created_at) and `MessageDeleted` (payload: id, sender_id; no body).
    - Channel: private `user.{id}`. The authorization callback returns true only when `{id}` equals the current user's id (AC-33).
    - `[RECOMMENDED]` Broadcast `MessageSent` to the recipient only. The sender's screen updates from the form response, so nobody sees a message twice.
    - `[RECOMMENDED]` Use the sync queue driver for local development so broadcasts run immediately. Reverb must be running during development.
    - Stretch (FR-18, FR-19): a presence channel `online`, and a private channel per pair `dm.{lowerId}.{higherId}` for typing signals.
- **Notifications and queues:** None beyond broadcasting.

## 11. Frontend Requirements

- **Pages:** `Chat/Index` (sidebar plus empty pane), `Chat/Show` (sidebar plus conversation), `Profile/Edit` (the starter profile page, extended with the two forms).
- **Components:** `Sidebar`, `ConversationRow`, `NewChatDialog`, `UserSearchResults`, `ChatHeader`, `MessageList`, `MessageItem`, `MessageComposer`, `DeleteMessageDialog`, `ProfileDetailsForm`, `AvatarForm`, `RateLimitNotice`. Reuse shadcn Button, Input, Textarea, Label, Avatar, Dialog, AlertDialog, DropdownMenu, and Sonner (AS-8).
- **Props and state:** The server passes `conversations` (partner id, name, username, avatar URL, unread count, last message time), `partner`, and `messages` (id, sender_id, body or null, is_deleted, created_at). The server data is the source of truth after any form submit. Real-time events add or mark messages locally and are matched by message id so a message is never shown twice.
- **Form handling:** Inertia's `useForm`. The submit button is disabled while `processing` is true and during a rate-limit wait.
- **Request handling:** Send and delete use partial reloads that keep scroll position and state. The profile forms preserve scroll.
- **Validation feedback:** Server errors appear under their fields. `[RECOMMENDED]` Convert a 429 response into a normal form error, because Inertia's default is to show the raw error page in a modal.
- **Component interactions:** The delete flow is menu, then confirm dialog, then request. One real-time subscription to the user's private channel lives in the chat layout and is removed when the layout unmounts. Focus returns to the composer after sending.

## 12. Validation and Error Handling

**Input validation**

| Field             | Rules                                                                                                                             | Error message                                                                         |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| name              | required, string, 2 to 50 characters                                                                                              | "Enter a name between 2 and 50 characters."                                           |
| username          | required, 3 to 20 characters, only `a-z 0-9 _`, unique (ignoring the user's own row)                                              | "Use 3 to 20 lowercase letters, numbers, or underscores." / "That username is taken." |
| bio               | optional, string, max 160 characters                                                                                              | "Bio can be at most 160 characters."                                                  |
| avatar            | required, image, JPG/PNG/WebP (checked by file content), max 2 MB                                                                 | "Choose a JPG, PNG, or WebP image up to 2 MB."                                        |
| message body      | required, string, max 1000 characters, not only whitespace                                                                        | "Write a message first." / "Messages can be at most 1000 characters."                 |
| new chat username | required, must exist, must not be the current user                                                                                | "No user with that username." / "You can't message yourself."                         |
| search `q`        | optional, max 20 characters. Under 2 characters returns an empty list, not an error. `%` and `_` are treated as plain characters. | none                                                                                  |

**Rate limit messages** (`{time}` is "N seconds" or "N minutes", taken from the `Retry-After` header)

| Limit | Message                                                   |
| ----- | --------------------------------------------------------- |
| RL-1  | "You've started too many new chats. Try again in {time}." |
| RL-2  | "Too many login attempts. Try again in {time}."           |
| RL-3  | "Too many searches. Try again in {time}."                 |
| RL-4  | "Too many photo uploads. Try again in {time}."            |
| RL-5  | "Too many profile changes. Try again in {time}."          |

- **Business validation:** BR-1 to BR-9 are enforced on the server. Deleting someone else's message returns 403.
- **Authorization failures:** 403 for forbidden actions. A refused channel subscription is rejected by the server. The page shows "You can't do that."
- **Database errors:** If two users save the same username at the same moment, the unique index rejects the second one and the form shows "That username is taken."
- **Unexpected errors:** Show "Something went wrong. Try again." Log the error on the server. Never show stack traces. Never log message text.

## 13. Security Requirements

- **Authentication:** Breeze session login. All chat, search, profile, and message routes require login (FR-16).
- **Authorization (server-side on every action):** `MessagePolicy@delete` for deletion; chat pages only ever query messages between the current user and the requested user (BR-1); private channel authorization (section 10).
- **Input sanitization and output escaping:** Messages and bios are plain text. React escapes them. `dangerouslySetInnerHTML` is not used (BR-4). Search wildcards are escaped (section 12).
- **Mass-assignment protection:** `$fillable` on `User` and `Message`. `sender_id` always comes from the logged-in user, never from the request.
- **Data protection:** Avatar files are validated by content type and size, stored under a random file name, and never keep the uploaded file name. Deleted message text is never sent to the browser (BR-2). Message text is not written to logs.
- **Rate limiting / abuse prevention:**

| ID   | Where                                           | Limit         | Counted per        |
| ---- | ----------------------------------------------- | ------------- | ------------------ |
| RL-1 | Start a new chat (first message to someone new) | 5 per hour    | user               |
| RL-2 | Login                                           | 5 per minute  | email + IP address |
| RL-3 | Username search                                 | 30 per minute | user               |
| RL-4 | Avatar upload                                   | 3 per hour    | user               |
| RL-5 | Profile details update                          | 10 per hour   | user               |

`[ASSUMPTION]` The starter kit already throttles login (RL-2). In that case the task is to read how it works, confirm it matches this table, and test it.

## 14. Acceptance Criteria

| ID    | Covers              | Criterion                                                                                                                                                                                                                                       |
| ----- | ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-1  | FR-16               | **Given** I am logged out, **when** I open `/chat`, **then** I am sent to the login page.                                                                                                                                                       |
| AC-2  | FR-1, BR-5          | **Given** I enter a username another user has, **when** I save, **then** "That username is taken." shows under that field and my other fields keep their values.                                                                                |
| AC-3  | FR-3                | **Given** I save a new name, **when** the save succeeds, **then** the sidebar footer shows the new name without a manual refresh.                                                                                                               |
| AC-4  | FR-2                | **Given** I choose a 5 MB image or a PDF, **when** I submit, **then** I see the avatar error message and my current avatar is unchanged.                                                                                                        |
| AC-5  | FR-15, RL-4         | **Given** I uploaded 3 avatars in the last hour, **when** I try a 4th, **then** I see "Too many photo uploads. Try again in {time}.", the preview stays, and "Save photo" is disabled until the wait ends.                                      |
| AC-6  | FR-5                | **Given** I type a username that does not exist, **when** I click "Start chat", **then** I see "No user with that username."                                                                                                                    |
| AC-7  | FR-5, BR-6          | **Given** I enter my own username, **when** I click "Start chat", **then** I see "You can't message yourself."                                                                                                                                  |
| AC-8  | RL-1, BR-7          | **Given** I started 5 new chats this hour, **when** I start a 6th, **then** I am blocked with the RL-1 message, **and** I can still send messages in my existing chats.                                                                         |
| AC-9  | FR-4                | **Given** I type 10 characters quickly in the Username field, **when** I pause for 300 ms, **then** only one search request is sent.                                                                                                            |
| AC-10 | RL-3, FR-15         | **Given** I made 30 searches this minute, **when** I make a 31st, **then** the search area shows "Too many searches. Try again in {time}."                                                                                                      |
| AC-11 | FR-7                | **Given** I type only spaces, **when** I press Enter, **then** nothing is sent and I see "Write a message first." **And given** a send fails, **then** my text stays in the box and "Message not sent. Try again." shows.                       |
| AC-12 | FR-9, NFR-1         | **Given** Ana has our chat open, **when** I send a message, **then** it appears on her screen within 1 second without a refresh.                                                                                                                |
| AC-13 | FR-14               | **Given** Ana has a different chat open, **when** I message her, **then** my chat's badge in her sidebar rises by 1 instantly.                                                                                                                  |
| AC-14 | FR-14               | **Given** a chat badge shows 3, **when** I open that chat and refresh the page, **then** the badge stays hidden.                                                                                                                                |
| AC-15 | FR-10, FR-11, FR-12 | **Given** I own a message, **when** I choose Delete and confirm, **then** both of us see "This message was deleted" in its place, and Ana's screen updates without a refresh. **And given** I choose Cancel, **then** the message is unchanged. |
| AC-16 | BR-2                | **Given** a message is deleted, **when** I inspect the page data and the real-time payload, **then** its text is not in either.                                                                                                                 |
| AC-17 | BR-3, FR-12         | **Given** Ana has 1 unread message from me, **when** I delete it, **then** her badge drops to 0 instantly.                                                                                                                                      |
| AC-18 | FR-10, BR-8         | **Given** a message was sent by Ana, **when** I look at it, **then** it has no options menu, **and** a DELETE request I send for it returns 403.                                                                                                |
| AC-19 | RL-2, FR-16         | **Given** I failed to log in 5 times in a minute, **when** I try a 6th time, **then** I am blocked with the RL-2 message.                                                                                                                       |
| AC-20 | FR-16               | **Given** I register as "Ana Cruz", **when** registration succeeds, **then** my username is lowercase letters, digits, and underscores, 20 characters or fewer, unique, and looks like `ana_cruz_4821`.                                         |
| AC-21 | FR-17               | **Given** Ana has a bio, **when** I open our chat, **then** the header shows her avatar, name, @username, and the bio on one truncated line.                                                                                                    |
| AC-22 | FR-18               | **Given** Ana is online, **when** I look at the sidebar, **then** a dot shows next to her, and it disappears within 10 seconds after she closes her tab. _(stretch)_                                                                            |
| AC-23 | FR-19               | **Given** Ana types in our chat, **when** she stops for 3 seconds, **then** "Ana is typing…" disappears. _(stretch)_                                                                                                                            |
| AC-24 | FR-6                | **Given** I have chats with Ana and Ben and Ben messaged last, **when** I open the app, **then** Ben is listed above Ana.                                                                                                                       |
| AC-25 | FR-8                | **Given** a chat has 60 messages, **when** I open it, **then** I see the latest 50 in time order with the newest at the bottom, my messages on the right, and a date line where the day changes.                                                |
| AC-26 | FR-13               | **Given** I already deleted a message, **when** my client sends the delete request again, **then** it succeeds and nothing changes.                                                                                                             |
| AC-27 | BR-1                | **Given** Ana and Ben have a chat, **when** I (Carl) open `/chat/{Ana}`, **then** I see none of the messages between Ana and Ben.                                                                                                               |
| AC-28 | BR-4                | **Given** I send `<b>hi</b>`, **when** the message shows, **then** it displays as the literal text `<b>hi</b>`.                                                                                                                                 |
| AC-29 | FR-2, BR-9          | **Given** I have an avatar, **when** I upload a new valid one, **then** the old file is removed from storage.                                                                                                                                   |
| AC-30 | NFR-2               | **Given** each screen, **when** it is loading, empty, failed, or disconnected, **then** it shows the state's exact text from `DESIGN.md`.                                                                                                       |
| AC-31 | NFR-3               | **Given** a 320 px wide screen, **when** I use every page, **then** nothing scrolls horizontally and the chat list and chat show one at a time.                                                                                                 |
| AC-32 | RL-5, FR-15         | **Given** I saved my details 10 times this hour, **when** I save an 11th time, **then** I see the RL-5 message and my typed values stay.                                                                                                        |
| AC-33 | Section 10, BR-1    | **Given** I am logged in as Carl, **when** I try to listen to `user.{Ana's id}`, **then** the server refuses.                                                                                                                                   |
| AC-34 | NFR-5               | **Given** I use only the keyboard, **when** I search, start a chat, send, and delete a message, **then** every step works and the focus ring is visible.                                                                                        |

## 15. Testing Requirements

- **Unit tests:** Username generator (FR-16): spaces, non-letter characters, empty result, 15-character cut, collision retry. Unread count rule (BR-3).
- **Feature tests (Pest):** Every route in section 10 for success, validation failure, and unauthenticated access.
- **Authorization tests:** Only the sender can delete (403 for anyone else); the chat page never returns messages between two other people; channel authorization refuses another user's channel.
- **Validation tests:** Every rule in section 12, including avatar size and type, username format and uniqueness, self-DM, and whitespace-only messages.
- **Rate limit tests:** The 6th new chat, 4th avatar, 11th profile save, and 31st search are blocked. Messages in an existing chat are never blocked.
- **Edge case tests:** Deleted body is absent from page data; deleting twice; unread count after the sender deletes; two users saving the same username.
- **Integration / E2E (optional):** Two browser sessions: start a chat, send a message, delete it, and confirm both screens update.
- **Frontend component tests (optional):** Debounced search sends one request.
- **Minimum for NFR-4 (8 tests):** sender-only delete, deleted body absent, new-chat limit, avatar validation, username unique, no self-DM, unread drops on delete, search limit.

## 16. Dependencies and Assumptions

- **Existing modules / files relied on:** None verified. This is a new app and no repository was inspected.
- **External services:** None. Laravel Reverb runs locally.
- **Required packages:** Laravel Breeze (React starter), Laravel Reverb, Laravel Echo with its client library, shadcn/ui components listed in section 11. No other packages.
- **Assumptions needing confirmation**

| ID   | Assumption                                                                                                                   | Impact if wrong                                                                                                              |
| ---- | ---------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| AS-1 | Stack is Laravel, Inertia, React (JSX), Tailwind, shadcn/ui, MySQL 8 or newer, Pest.                                         | File names, paths, and test syntax change. On older MySQL the CHECK constraint is ignored, so rely on app validation (BR-6). |
| AS-2 | Laravel Breeze (React + Inertia) provides auth and a starter profile page.                                                   | Auth tasks (BE-1) get larger.                                                                                                |
| AS-3 | Breeze already throttles login attempts.                                                                                     | RL-2 has to be built from scratch.                                                                                           |
| AS-4 | Laravel Reverb works locally for real-time.                                                                                  | Swap to another broadcaster; the event and channel design stays the same.                                                    |
| AS-5 | Usernames are generated at registration (FR-16) and editable in the profile.                                                 | Add a Username field to the registration form.                                                                               |
| AS-6 | If the starter profile page offers "Delete account", it is hidden in v1.                                                     | A rule for what happens to a deleted user's messages is needed first.                                                        |
| AS-7 | Email verification is not required.                                                                                          | Add the `verified` middleware and a verification screen.                                                                     |
| AS-8 | shadcn/ui is installed with Button, Input, Textarea, Label, Avatar, Dialog, AlertDialog, DropdownMenu, and Sonner available. | Add the missing components or build equivalents.                                                                             |

## 17. Out of Scope

- Group rooms (v2: refactor `messages` into conversations, a pivot table, and a data migration for existing messages)
- Editing a sent message
- Images, files, emoji, and reactions in messages
- Searching inside messages
- Loading messages older than the latest 50
- Blocking and reporting users
- Push or email notifications
- "Seen" read receipts
- Account deletion and password-reset changes beyond the starter defaults
- Dark mode (tokens are defined in `DESIGN.md`, but nothing is built for it)
- Rate limiting messages inside an existing chat
- Admin and moderation tools

## 18. Implementation Breakdown

**Phase 1: Database** (Day 1)

- [ ] DB-1: Migration adding `username`, `bio`, `avatar_path` to `users` (FR-1, FR-2, BR-5)
- [ ] DB-2: Migration creating `messages` with indexes and constraints (FR-7, BR-2, BR-3)
- [ ] DB-3: Factories and a seeder with 5 users and sample messages

**Phase 2: Backend** (Days 1 to 6)

- [ ] BE-1: Install Breeze; generate usernames at registration (FR-16, AC-20)
- [ ] BE-2: `ProfileController@update` and `UpdateProfileRequest` (FR-1)
- [ ] BE-3: `AvatarController@store` and `UpdateAvatarRequest`; remove the old file (FR-2, BR-9)
- [ ] BE-4: Named rate limiters for RL-1 to RL-5 (section 13)
- [ ] BE-5: Turn 429 responses into form errors with a wait time (FR-15)
- [ ] BE-6: `UserSearchController@index` (FR-4, RL-3)
- [ ] BE-7: `DirectMessageController@store` with RL-1 applied only for new pairs (FR-5, BR-6, BR-7)
- [ ] BE-8: `ChatController@index` and `@show` with unread counts and header data (FR-6, FR-8, FR-14, FR-17)
- [ ] BE-9: `MessageController@store` and `StoreMessageRequest` (FR-7)
- [ ] BE-10: `MessageSent` and `MessageDeleted` events and `user.{id}` channel authorization (FR-9, BR-1)
- [ ] BE-11: `MessageController@destroy` and `MessagePolicy` (FR-10 to FR-13, BR-2, BR-3, BR-8)
- [ ] BE-12: `ChatReadController@store` (FR-14)
- [ ] BE-13: Read and verify the login throttle (RL-2)

**Phase 3: Frontend** (Days 1 to 7)

- [ ] FE-1: Profile details form (FR-1, FR-3)
- [ ] FE-2: Avatar form with preview (FR-2)
- [ ] FE-3: Chat layout with sidebar and conversation pane (FR-6, FR-8)
- [ ] FE-4: New chat dialog with debounced search (FR-4, FR-5)
- [ ] FE-5: Message list and composer (FR-7, FR-8)
- [ ] FE-6: Echo setup and one subscription in the chat layout (FR-9)
- [ ] FE-7: Message menu, delete confirm dialog, deleted placeholder (FR-10 to FR-12)
- [ ] FE-8: Unread badges (FR-14)
- [ ] FE-9: `RateLimitNotice` with countdown on every limited form (FR-15)
- [ ] FE-10: Loading, empty, failed, and disconnected states (NFR-2)
- [ ] FE-11: _(stretch)_ Online dot and typing indicator (FR-18, FR-19)

**Phase 4: Testing and polish** (Day 7)

- [ ] QA-1: The 8 tests listed in section 15 (NFR-4)
- [ ] QA-2: Walk through AC-1 to AC-34 by hand with two browser windows
- [ ] QA-3: Keyboard and 320 px checks (NFR-3, NFR-5)
- [ ] QA-4: One small refactor (for example, move the unread query into a model scope)

**Day plan (3 to 4 hours a day):** Day 1 database, auth, profile details form. Day 2 avatar form and RL-4, RL-5. Day 3 search, new chat, send and view messages (no real-time yet). Day 4 Reverb and live messages. Day 5 delete message. Day 6 unread badges and the login throttle review. Day 7 tests, refactor, buffer, and the stretch items only if time remains.

**Cut line:** If behind schedule, drop FR-18 and FR-19 first, then reduce tests to the 8 minimum. Never cut BR-2.
