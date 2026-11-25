# Product Requirements Document: DM Chat App (v2.0, as built)

| Field            | Value                                                                                                 |
| ---------------- | ----------------------------------------------------------------------------------------------------- |
| Version          | 2.0 (describes the application that exists in this repository)                                       |
| Date             | 2026-11-25                                                                                            |
| Project          | DM Chat App, a one-to-one chat application with gated friend requests                                |
| Stack            | Laravel 13, Inertia.js 3, React 19 (TypeScript), Tailwind CSS 4, shadcn/ui, MySQL 8, Laravel Fortify, Laravel Reverb, Laravel Echo, Pest 5, Wayfinder |
| Companion file   | `DESIGN.md` (visual design, exact copy, component specs)                                             |
| Status           | Implemented and tested. 110 tests pass, 4 skipped.                                                   |
| Source of truth  | `routes/`, `app/`, `resources/js/`, `database/migrations/`, `tests/`                                  |

This version replaces the earlier draft. It describes what the app does today. Features from the draft that were never built are listed in section 15, and the places where the shipped app is weaker than the draft promised are listed in section 14. Nothing in this document is aspirational unless it is in section 15.

Tags used: `[BUILT]` exists in the code. `[KNOWN GAP]` exists but has a known weakness, described in section 14. `[DEFERRED]` is listed in section 15 and is not built.

---

## 1. Feature Overview

- **Feature name:** DM Chat App v1 (direct messages plus gated friend requests).
- **Description:** A logged-in user can register, edit a profile, find people by username, send a friend request, accept incoming requests, remove friends, and exchange one-to-one messages. Messages in the open thread appear in real time over Reverb. A hidden message disappears from the recipient's copy of the thread; the sender keeps it.
- **Purpose:** Exercise authentication, authorization, validation, relational state, rate limiting, broadcasting, and React/Inertia patterns on the Laravel stack.
- **Problem statement:** Two people need a private place to talk. A conversation should start only after both people agree to connect, and messages should arrive without a page refresh.

## 2. Goals and What Is Proven

**Primary goals (all built):**

1. Two accepted friends can hold a private conversation in real time.
2. A new conversation cannot be opened with a stranger. A pair can open a thread if they are friends or have exchanged at least one message.
3. A user can find people by username prefix and manage the full friend-request lifecycle.
4. Users can edit name, username, bio, email, and avatar; avatars are rate limited.
5. Either participant can hide a message. The recipient stops seeing it; the sender keeps it.

**Measured coverage:**

| Measure                              | Value                                                                    |
| ------------------------------------ | ------------------------------------------------------------------------ |
| Automated tests                      | 110 passed, 4 skipped, 516 assertions (`php artisan test --compact`)      |
| Test coverage areas                  | auth, profile, avatar, search, friends, messages, deletion, active status |
| Rate limits enforced                 | login (5/min), avatar upload (3/hour)                                    |
| Real-time events                     | `message.sent`, `message.deleted` on private `user.{id}`                  |
| Real-time friend updates             | none (`[DEFERRED]`)                                                       |

## 3. Roles and Permissions

There is one role: a logged-in user. Guests can reach only the login, register, and password-reset pages.

| Action                                    | Guest | Logged-in user                                      |
| ----------------------------------------- | ----- | --------------------------------------------------- |
| Register, log in, reset password          | ✓     | ✗ (authenticated users are redirected to `/chat`)   |
| Open `/chat` and `/chat/{user}`           | ✗     | ✓                                                   |
| Search people by username                 | ✗     | ✓                                                   |
| Send a friend request                     | ✗     | ✓, not to themselves                                |
| Accept or decline a request               | ✗     | ✓, addressee only                                   |
| Cancel an outgoing request                | ✗     | ✓, requester only                                   |
| Remove a friend                           | ✗     | ✓, either participant                               |
| Read a thread                             | ✗     | ✓, if friends or a message history exists           |
| Send a message                            | ✗     | ✓, accepted friends only                            |
| Hide a message                            | ✗     | ✓, either participant                               |
| Edit profile, email, avatar               | ✗     | ✓                                                   |
| Use security and appearance settings      | ✗     | ✓, password confirmation for Security                |
| Listen to `user.{id}`                     | ✗     | ✓, only their own id                                |

**Authorization restrictions:**

- A thread query always filters on both participant ids. A third user never sees another pair's messages.
- `MessagePolicy@delete` allows either participant of that message. A stranger receives 403.
- Friend-request transitions are authorized inside `FriendshipService`. Unauthorized role or state returns 403 or a validation error.
- `routes/channels.php` authorizes `user.{id}` only when the id matches the authenticated user.

## 4. User Stories

- **US-1:** As a visitor, I want to register and log in, so that I have an account.
- **US-2:** As a user, I want to change my name, username, bio, and email, so that people recognise me.
- **US-3:** As a user, I want to upload an avatar, so that my chats feel personal.
- **US-4:** As a user, I want to find someone by username, so that I can send a friend request.
- **US-5:** As a user, I want to accept or decline requests, so that I control who can message me.
- **US-6:** As a user, I want to cancel a request I sent, so that I can undo a mistake.
- **US-7:** As a user, I want to remove a friend, so that my friends list stays accurate. My chat history stays.
- **US-8:** As a user, I want to open a friend from my chat list and write the first message.
- **US-9:** As a user, I want a message to appear for the other person without a refresh.
- **US-10:** As a user, I want to see unread counts, so that I know where to reply first.
- **US-11:** As a user, I want to remove a message from a conversation, so that it no longer appears for the reader.
- **US-12:** As a user, I want to see who is active, so that I know who is around.

## 5. Functional Requirements

### Accounts and profile

| ID   | Requirement                                                                                                                                                                                                                                      | Status  |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------- |
| FR-1 | Visitors can register with name, username, email, password, and password confirmation, then log in and log out. Username is chosen by the user: required, 3 to 20 characters, unique. It is not generated from the name. Guests who open `/chat` are redirected to login. | BUILT   |
| FR-2 | The profile page at `/settings/profile` edits name, username, bio, and email in one form. Errors appear under their fields and other values are preserved. A changed email resets `email_verified_at`. | BUILT   |
| FR-3 | The avatar form is separate from the details form. It shows a local preview of the chosen file before upload, accepts JPG, PNG, or WebP up to 2 MB, stores the file under `avatars/` on the public disk, and deletes the previous file. | BUILT   |
| FR-4 | Avatar uploads are limited to 3 per hour. A 429 response shows "Too many photo uploads. Try again in {N seconds or minutes}." and disables "Save photo" until the wait ends. The preview stays. | BUILT   |
| FR-5 | Settings provides Profile, Security (password and passkeys), and Appearance (light, dark, system). Account deletion exists in Profile. Two-factor support is disabled in config. | BUILT (with gaps 14.4 and 14.13) |

### People and friendships

| ID   | Requirement                                                                                                                                                                                                                                    | Status |
| ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| FR-6 | People search returns up to 10 users whose username starts with the typed text, excluding the current user. It runs at 2 or more characters, waits 300 ms after the last keystroke, escapes `%` and `_`, and caps the query at 20 characters. | BUILT  |
| FR-7 | Each search result shows avatar, name, `@username`, and one action: "Friends" (disabled), "Request sent" (disabled), "Accept" (incoming request), or "Add". The action is derived in the browser from the Friends page props. | BUILT  |
| FR-8 | Sending a request is idempotent in the same direction. A reverse pending request auto-accepts. An existing accepted friendship is returned unchanged. Sending to yourself is rejected. | BUILT  |
| FR-9 | The Friends page lists incoming requests ("Accept", "Decline"), outgoing requests ("Cancel request"), and accepted friends ("Remove"). Request sections render only when non-empty. | BUILT  |
| FR-10 | Only the addressee can accept or decline. Only the requester can cancel. Either participant can remove an accepted friendship. A pending row cannot be removed. | BUILT  |
| FR-11 | Removing a friend deletes the friendship row only. Messages and the existing thread stay. Sending becomes impossible until a new request is accepted; reading stays possible. | BUILT  |
| FR-12 | The chat sidebar shows a Friends row with the incoming pending-request count. The badge caps at "99+". The mobile tab bar caps the same count at "9+". | BUILT  |
| FR-13 | Friend actions are ordinary Inertia visits. Neither the other party's screen nor the local sidebar updates without a new request. | BUILT (no realtime) |

### Chat and messages

| ID   | Requirement                                                                                                                                                                                                                                      | Status |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------ |
| FR-14 | At `/chat`, the mobile list (`md:hidden`) lists every partner with message history, newest conversation first, followed by accepted friends with no messages in name order. Each row shows avatar, name, `@username`, last-message preview, relative time, read/sent tick, unread count, and an active dot. | BUILT  |
| FR-15 | The desktop sidebar uses the same data in a narrower row: avatar, name, `@username`, active dot, unread count, and a search field, but no preview or time. The mobile list adds All/Unread filters and an "Active friends" strip. Below 768 px the list is shown; at 768 px and above the sidebar is used. | BUILT  |
| FR-16 | `/chat/{user}` returns 404 for yourself and 403 for a user who is neither a friend nor has any message history with you. A friend or a pair with history can open the thread. | BUILT  |
| FR-17 | Opening a thread marks every incoming unread message from that partner as read, then returns the latest 50 messages oldest-first. A hidden message is returned with `body: null` to the recipient; the sender always receives the body. | BUILT  |
| FR-18 | Sending requires an accepted friendship and rejects sending to yourself. The body is 1 to 1000 characters and cannot be only whitespace. The sender always comes from the session. The message is broadcast to the recipient and the user is redirected back to the thread. | BUILT  |
| FR-19 | The composer is a two-row textarea and a "Send" button. "Send" is disabled when the text is empty or whitespace or while sending. Enter inserts a newline; it does not send. | BUILT  |
| FR-20 | While the thread is open, a message from that partner is appended without a refresh. A message from anyone else shows an info toast, "{Name} sent you a message", and is not added. | BUILT  |
| FR-21 | The message list shows the latest 50 messages, own messages on the right and the partner's on the left. Every message shows its local time. A date line appears where the day changes: "Today", "Yesterday", a weekday name within the last week, or a full date. The list scrolls to the newest message when the last id changes. | BUILT  |
| FR-22 | Empty thread text is "No messages yet. Say hello." Empty sidebar text is "No chats yet. Search for someone to start." Empty mobile list text is "No chats yet. Find a friend to start." | BUILT  |
| FR-23 | Every visible message, own or partner's, has a "Message options" menu with "Delete message". The confirm dialog says "Delete message" and explains that the message will be hidden from the other person (for an own message) or removed from your view (for the partner's message). | BUILT  |
| FR-24 | Confirming sets the shared `deleted_at` timestamp. The sender keeps the text; the recipient receives `body: null` and sees "This message was deleted", whoever triggered the delete. A second delete is a no-op. A stranger gets 403. The deletion is broadcast to both participants as an id only. | BUILT  |
| FR-25 | Hiding a message does not change either participant's unread count. The badge is recalculated only on the next page load, and the unread query does not exclude hidden messages. | KNOWN GAP (see 14.2) |
| FR-26 | After a friendship is removed, the thread stays readable but the composer is replaced by "You're no longer friends with {Name}. You can still read your conversation, but you can't send new messages." Sending returns 403. | BUILT  |
| FR-27 | A user is "active" while `last_seen_at` is within 5 minutes. The timestamp is touched on HTTP requests at most every 30 seconds. The dot appears in the conversation row, chat header, and Friends rows; it refreshes on the next request. | BUILT  |
| FR-28 | Mobile shows a fixed bottom tab bar with Chats, Friends, and Profile. It carries the total unread count and pending-request count, caps both at "9+", and is hidden on `/chat/{user}`. | BUILT  |

### Non-functional requirements

| ID    | Requirement                                                                                                                     | Status |
| ----- | ------------------------------------------------------------------------------------------------------------------------------- | ------ |
| NFR-1 | A message reaches an open thread within 1 second on a local machine when Reverb is running and the queue is synchronous. This is a manual check, not an automated test. | TARGET |
| NFR-2 | Screens define loading, empty, and error states. A dedicated "disconnected" banner is not implemented.                           | BUILT (partly) |
| NFR-3 | The mobile layout is single-column below 768 px and uses the tab bar. Desktop shows the chat sidebar and pane side by side.      | BUILT  |
| NFR-4 | 110 automated tests pass.                                                                                                       | BUILT  |
| NFR-5 | Keyboard support, focus rings, and 4.5:1 contrast are design targets. No automated accessibility audit is in the repo.          | TARGET |
| NFR-6 | Friend changes are not real time. They appear on the next request.                                                              | NOT BUILT |

## 6. Business Rules

| ID   | Rule                                                                                                                                                                                                                       |
| ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR-1 | Only the two participants can read a message. The thread query always filters on both sender and recipient.                                                                                                                |
| BR-2 | Hiding a message is a shared soft delete: `deleted_at` is set on the row. The body is kept. The sender keeps the text; the recipient receives `null`, whoever triggered the delete. The real-time payload carries only the id. |
| BR-3 | Unread count for a partner = incoming messages where `read_at` is null. Hidden messages are still counted. Opening the thread clears the count.                                                                            |
| BR-4 | Messages are plain text. React escapes them. `dangerouslySetInnerHTML` is not used.                                                                                                                                        |
| BR-5 | Usernames are unique and 3 to 20 characters. No lowercase or character-set rule is enforced by the server.                                                                                                                 |
| BR-6 | A user cannot open a thread with themselves (404) or send a message to themselves (404).                                                                                                                                   |
| BR-7 | Sending requires an accepted friendship. A prior message history alone allows reading, not sending.                                                                                                                        |
| BR-8 | Either participant can hide a message. There is no separate sender-only rule.                                                                                                                                               |
| BR-9 | A saved avatar replaces the previous file. A failed upload leaves the current avatar untouched.                                                                                                                             |
| BR-10 | A friendship row is unique per `(requester_id, addressee_id)`. Reverse and duplicate requests are resolved inside one locked transaction. Accepted is final until removal.                                                   |
| BR-11 | Removing a friend deletes the row only. Messages stay.                                                                                                                                                                     |
| BR-12 | Search is a username prefix match, case-insensitive, excludes the current user, and returns at most 10 rows.                                                                                                               |
| BR-13 | The Friends badge counts incoming pending rows only. The mobile Chats badge counts all unread incoming messages, including hidden ones.                                                                                     |
| BR-14 | Active means `last_seen_at` is within 5 minutes. The value is request-driven, not presence-driven.                                                                                                                          |

**Message state transitions**

| From    | To     | Allowed by           | Condition                       |
| ------- | ------ | -------------------- | ------------------------------- |
| Unread  | Read   | Recipient            | Opens the thread                |
| Visible | Hidden | Sender or recipient  | `deleted_at` is null            |
| Hidden  | Hidden | Anyone               | No-op, `deleted_at` unchanged   |

**Friendship state transitions**

| From                 | To       | Allowed by        | Condition                                     |
| -------------------- | -------- | ----------------- | --------------------------------------------- |
| None                 | Pending  | Requester         | Not self, no existing row                     |
| Pending              | Accepted | Addressee         | Request addressed to them                     |
| Pending + reverse    | Accepted | Second requester  | The second request auto-accepts the first     |
| Pending              | None     | Addressee         | Decline deletes the row                       |
| Pending              | None     | Requester         | Cancel deletes the row                        |
| Accepted             | None     | Either user       | Remove deletes the row; messages stay         |

## 7. User Workflows

**Workflow A: Register and complete a profile**

1. Visitor opens Register and enters name, username, email, password, and confirmation.
2. The user is created, logged in, and sent to `/chat`.
3. The user opens Profile from the avatar menu or the mobile tab and can change name, username, bio, email, and photo.

**Workflow B: Find and add a friend**

1. User opens Friends and clicks "Find people".
2. User types at least 2 characters. After 300 ms the dialog shows up to 10 matches with their relationship action.
3. User clicks "Add". The request is stored, and the row changes to "Request sent" on the next render.
4. The recipient sees the request under "Friend requests" with a badge on the Friends row.

**Workflow C: Accept or decline**

1. Recipient clicks "Accept". The row becomes an accepted friendship, and both users can now open a thread.
2. Recipient clicks "Decline". The pending row is deleted. The sender can request again later.

**Workflow D: Start a thread**

1. A new accepted friend appears in the sidebar conversation list (and the mobile list) with no messages.
2. The user opens that row and sends the first message.
3. If a thread already exists, the message joins it.

**Workflow E: Send and receive**

1. User types and clicks "Send".
2. The message is saved and broadcast to the recipient.
3. If the recipient has that thread open, the message is appended. If they have a different thread open, they get an info toast instead. If they are on the chat list or another page, nothing updates until their next request.

**Workflow F: Hide a message**

1. User opens "Message options" on any visible message and chooses "Delete message".
2. The confirm dialog explains the effect. User confirms.
3. The recipient sees "This message was deleted". The sender keeps the text. If the recipient hid the sender's message, it disappears from the recipient's view while the sender still sees it.

**Workflow G: Remove a friend**

1. User clicks "Remove" on a friend and confirms.
2. The friendship row is deleted. The friend leaves the friends list.
3. The existing thread stays readable but is read-only.

**Failure scenarios**

- **Websocket disconnected:** no banner. The open thread simply stops receiving live updates until the connection returns. New page requests still show the latest data.
- **Send fails validation:** the text stays in the composer. No inline error is shown in the composer. `[KNOWN GAP]`
- **Avatar validation fails:** the error appears under the field and the current avatar is unchanged.
- **Avatar rate limited:** the message and disabled button appear; the preview stays.
- **Search fails:** "Couldn't search right now. Try again." replaces the results.
- **Friend action fails:** a fallback error appears under the search results or the Inertia error response is shown.
- **Delete fails:** "Couldn't delete the message. Try again." appears inside the dialog.
- **Open a stranger's thread:** 403.

## 8. UI/UX Requirements

Visual design, tokens, wireframes, and the exact copy deck live in `DESIGN.md`. This section lists what must exist.

- **Pages:** Chat index (`/chat`), chat thread (`/chat/{user}`), Friends (`/friends`), Profile (`/settings/profile`), Security (`/settings/security`), Appearance (`/settings/appearance`), auth pages.
- **Chat surfaces:** desktop sidebar with Friends row, search, conversation rows, and user footer; mobile chat list with search, All/Unread, Active friends, and rows; conversation header; message list; composer; message options menu; delete dialog.
- **Friends surfaces:** Friends page with three sections; Find people dialog; remove-friend dialog; incoming and outgoing action buttons.
- **Profile surfaces:** details form, photo form, delete-account block, settings side navigation.
- **Loading:** search shows a spinner while fetching; no full-page spinner is defined.
- **Empty states:** listed in FR-22 and in `DESIGN.md` section 6.
- **Error states:** field errors under fields; friend-search and delete errors near the control that failed; 403 and 404 use Inertia's default error response.
- **Success feedback:** profile, photo, and password saves render Sonner toasts through `Inertia::flash('toast', …)` and the `useFlashToast` hook mounted inside the root `Toaster`. Friend actions use `back()->with('success', …)`, which does not reach the Inertia flash prop, so those confirmations do not appear (`[KNOWN GAP]`). The info toast for a message from a different conversation is also visible.
- **Responsive:** below 768 px, one surface at a time plus the bottom tab bar. At 768 px and above, the chat sidebar and pane sit side by side. The Friends page uses a centered `max-w-2xl` column; settings content is capped at `max-w-2xl` beside the settings navigation.
- **Accessibility target:** keyboard reachable controls, visible focus rings, labelled dialogs, named icon buttons, errors tied to fields, and 4.5:1 contrast.

## 9. Database Requirements

The schema is defined by the committed migrations.

**Table: `users`**

| Column                       | Type         | Null | Notes                                                |
| ---------------------------- | ------------ | ---- | ---------------------------------------------------- |
| id                           | bigint       | no   | PK                                                   |
| name                         | varchar(255) | no   |                                                      |
| username                     | varchar(20)  | no   | Unique                                               |
| bio                          | varchar(160) | yes  |                                                      |
| avatar_path                  | varchar(255) | yes  | Path on the public disk                              |
| email                        | varchar(255) | no   | Unique                                               |
| email_verified_at            | timestamp    | yes  |                                                      |
| password                     | varchar(255) | no   | Hashed                                               |
| two_factor_secret            | text         | yes  | Fortify                                              |
| two_factor_recovery_codes    | text         | yes  | Fortify                                              |
| two_factor_confirmed_at      | timestamp    | yes  | Fortify                                              |
| last_seen_at                 | timestamp    | yes  | Active-status source                                 |
| remember_token               | varchar(100) | yes  |                                                      |
| created_at, updated_at       | timestamp    | no   |                                                      |

**Table: `messages`**

| Column                 | Type            | Null | Notes                                     |
| ---------------------- | --------------- | ---- | ----------------------------------------- |
| id                     | bigint unsigned | no   | PK                                        |
| sender_id              | bigint unsigned | no   | FK `users.id`, restrict on delete         |
| recipient_id           | bigint unsigned | no   | FK `users.id`, restrict on delete         |
| body                   | varchar(1000)   | yes  | Kept after hiding (BR-2)                  |
| read_at                | timestamp       | yes  | Set when the recipient opens the thread   |
| deleted_at             | timestamp       | yes  | Shared hide flag                          |
| created_at, updated_at | timestamp       | no   | Stored in UTC, shown in browser local time |

Indexes: `messages_pair_idx (sender_id, recipient_id, id)`, `messages_inbox_idx (recipient_id, sender_id, read_at, id)`.

**Table: `friendships`**

| Column                 | Type            | Null | Notes                                |
| ---------------------- | --------------- | ---- | ------------------------------------ |
| id                     | bigint unsigned | no   | PK                                   |
| requester_id           | bigint unsigned | no   | FK `users.id`, restrict on delete    |
| addressee_id           | bigint unsigned | no   | FK `users.id`, restrict on delete    |
| status                 | varchar(10)     | no   | `pending` or `accepted`              |
| accepted_at            | timestamp       | yes  |                                      |
| created_at, updated_at | timestamp       | no   |                                      |

Unique: `(requester_id, addressee_id)`. Indexes: `(addressee_id, status)`, `(requester_id, status)`.

**Other tables:** `passkeys`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

**Relationships and rules:**

- A message belongs to a sender and a recipient (both `User`). A friendship belongs to a requester and an addressee (both `User`).
- Accepted friends are the union of accepted rows in either direction.
- All message and friendship foreign keys use `restrictOnDelete`. A user with messages or friendships cannot be deleted, which affects the built-in account deletion (`[KNOWN GAP]`).
- No `CHECK` constraints exist. Self-pair rules are enforced in the app.
- The friendship migration backfills accepted friendships from existing distinct message pairs before the gate takes effect.

**Migrations:** `create_users_table`, `create_messages_table`, `create_friendships_table` (with backfill), `add_last_seen_at_to_users_table`, `create_passkeys_table`, `add_two_factor_columns_to_users_table`.

## 10. Backend Requirements

**Routes**

| Method | URI                                        | Name                   | Controller@action             | Middleware                    |
| ------ | ------------------------------------------ | ---------------------- | ----------------------------- | ----------------------------- |
| GET    | `/`                                        | home                   | closure: redirect             | web                           |
| GET    | `/chat`                                    | chat.index             | ChatController@index          | auth                          |
| GET    | `/chat/{user}`                             | chat.show              | ChatController@show           | auth                          |
| POST   | `/chat/{user}/messages`                    | messages.store         | MessageController@store       | auth                          |
| DELETE | `/messages/{message}`                      | messages.destroy       | MessageController@destroy     | auth                          |
| GET    | `/users/search`                            | users.search           | UserSearchController@index    | auth                          |
| POST   | `/friend-request/{user}`                   | friend-request.store   | FriendRequestController@store | auth                          |
| PATCH  | `/friend-request/{friendship}/accept`      | friend-request.accept  | FriendRequestController@accept | auth                         |
| DELETE | `/friend-requests/{friendship}/decline`    | friend-requests.decline| FriendRequestController@decline | auth                        |
| DELETE | `/friend-requests/{friendship}/cancel`     | friend-requests.cancel | FriendRequestController@cancel | auth                         |
| DELETE | `/friends/{user}`                          | friends.destroy        | FriendRequestController@destroy | auth                        |
| GET    | `/friends`                                 | friends.index          | FriendshipController@index    | auth                          |
| GET    | `/settings/profile`                        | profile.edit           | Settings\ProfileController@edit | auth                        |
| PATCH  | `/settings/profile`                        | profile.update         | Settings\ProfileController@update | auth                      |
| POST   | `/settings/profile/avatar`                 | profile.avatar         | Settings\AvatarController@store | auth, throttle `avatar-upload` |
| DELETE | `/settings/profile`                        | profile.destroy        | Settings\ProfileController@destroy | auth, verified          |
| GET    | `/settings/security`                       | security.edit          | Settings\SecurityController@edit | auth, verified, password confirm |
| PUT    | `/settings/password`                       | user-password.update   | Settings\SecurityController@update | auth, verified, throttle `6,1` |
| GET    | `/settings/appearance`                     | appearance.edit        | Inertia page                   | auth, verified                |
| GET    | `/.well-known/passkey-endpoints`           | well-known.passkeys    | closure                        | web                           |

Fortify supplies `/login`, `/register`, `/logout`, `/forgot-password`, `/reset-password`, `/user/confirm-password`, and the passkey endpoints. Email verification and the two-factor challenge are not registered while those features are disabled.

There is no `/dms`, no `/chat/{user}/read`, and no rate-limited search, friend-request, or profile-update route.

**Controllers**

- `ChatController@index` returns `conversations` and `activeId: null`.
- `ChatController@show` rejects self (404), rejects non-friend without history (403), marks incoming messages read, loads the latest 50 ascending, maps hidden bodies to `null` for the recipient, and returns `canMessage: $isFriend`.
- `MessageController@store` validates, rejects self (404), rejects non-friends (403), creates the message, dispatches `MessageSent`, and redirects back to the thread. Broadcast failures are logged and do not fail the save.
- `MessageController@destroy` authorizes, returns early when already hidden, sets `deleted_at`, dispatches `MessageDeleted`, and redirects back.
- `UserSearchController@index` validates `q`, trims, returns `[]` below 2 characters, escapes LIKE wildcards, and returns up to 10 users ordered by username.
- `FriendRequestController` delegates every transition to `FriendshipService` and flashes a success message.
- `FriendshipController@index` returns accepted friends, incoming pending requests, outgoing pending requests, conversations, and `activeId: null`.
- `Settings\ProfileController` updates the profile (and clears email verification on an email change), and deletes the account.
- `Settings\AvatarController` stores the new avatar, deletes the old file, and flashes "Photo updated."
- `Settings\SecurityController` renders the password form and passkey list, and updates the password. Two-factor support is coded but `Features::twoFactorAuthentication()` is commented out in `config/fortify.php`, so the 2FA UI is not shown.

**Form Requests**

- `StoreMessageRequest`: body required, string, max 1000, not only whitespace.
- `Settings\ProfileUpdateRequest`: name, username, bio, email.
- `Settings\AvatarUpdateRequest`: avatar required, image, jpg/jpeg/png/webp, max 2048, with custom messages.
- `Settings\PasswordUpdateRequest`, `Settings\ProfileDeleteRequest`, `Settings\TwoFactorAuthenticationRequest` for security.

**Models and services**

- `User`: `sentMessages`, `receivedMessages`, `sentFriendRequests`, `receivedFriendRequests`, `incomingFriendRequests`, `outgoingFriendRequests`, `friends()`, `isFriendsWith()`, `hasConversationWith()`, `isActive()`, `conversations()`, `avatar` accessor.
- `Message`: `sender`, `recipient`, casts for `read_at` and `deleted_at`.
- `Friendship`: `requester`, `addressee`, status enum cast.
- `FriendshipService`: send, accept, decline, cancel, remove. Every transition runs in a transaction, locks both user rows in id order, then locks the friendship row.
- `FriendshipPolicy` does not exist. Authorization is in the service.

**Real-time**

- `MessageSent` implements `ShouldBroadcast`, broadcasts on `private-user.{recipient_id}` as `message.sent` with id, sender and recipient ids, sender name, body, read_at, and timestamps.
- `MessageDeleted` implements `ShouldBroadcast`, broadcasts on both participants' private channels as `message.deleted` with the id only.
- `routes/channels.php` authorizes `user.{id}` only for the matching authenticated user.
- No friend events exist.
- The local `.env` uses `QUEUE_CONNECTION=sync`, so events broadcast immediately. `.env.example` uses `database`, which requires a queue worker.

**Notifications and queues:** no email, push, or browser notifications. No queued jobs beyond the standard framework tables.

## 11. Frontend Requirements

**Pages**

- `chat/index`: mobile chat list plus the desktop empty state.
- `chat/show`: header, message list, composer or read-only notice.
- `friends/index`: Friends page.
- `settings/profile`, `settings/security`, `settings/appearance`.
- `auth/*`: Fortify-driven login, register, password reset, confirmation, and passkey sign-in.

**Components**

`ChatLayout`, `ChatSidebar`, `ChatSidebarHeader`, `ConversationList`, `ConversationRow`, `ChatHeader`, `MessageList`, `MessageBubble`, `MessageComposer`, `EmptyChatState`, `ActiveStatus`, `MobileTabBar`, `FriendAvatar`, `FriendRow`, `FindPeopleDialog`, `IncomingRequestActions`, `OutgoingRequestActions`, `RemoveFriendDialog`, `AvatarForm`, `AppSidebar`, `NavUser`, `UserMenuContent`, `SettingsLayout`, `AppearanceTabs`, `DeleteUser`, plus shadcn primitives (Button, Input, Textarea, Label, Avatar, Dialog, AlertDialog, DropdownMenu, Sonner, Sidebar).

**Props and state**

- Shared: `auth.user`, `sidebarOpen`, `pendingFriendRequestsCount`, `unreadMessagesCount`.
- Chat index: `conversations`, `activeId`.
- Chat show: `user`, `messages`, `conversations`, `activeId`, `canMessage`.
- Friends: `friends`, `incomingRequests`, `outgoingRequests`, `conversations`, `activeId`.
- Conversation shape: id, name, username, avatar, unread_count, is_active, last_message, last_message_at, last_message_is_mine, last_message_is_deleted, last_message_is_read.
- Message shape: id, sender_id, recipient_id, body|null, is_deleted, read_at, created_at. The TypeScript type also declares `updated_at`; the show payload does not send it, while the real-time sent payload does.
- Search results: id, name, username, avatar. Relationship state is computed in the browser.

**Form handling**

- Profile and auth use Inertia `Form` / `useForm`.
- Avatar uses `useForm` with a blob preview and an `onHttpException` handler for 429.
- Friend actions use `router.post/patch/delete` with `preserveScroll`. Local pending state disables the row while the request runs.
- Message send uses `form.post` with `preserveScroll`, `preserveState`, and `only: ['messages']`.
- Message delete uses `router.delete` with `preserveScroll` and a local error flag.

**Real-time wiring**

- `chat/show` subscribes to `user.{auth.user.id}` for `.message.sent` and `.message.deleted` using `useEcho`.
- Sending appends only messages from the open partner. Messages from others raise an info toast.
- `chat/index` and `friends/index` do not subscribe. Their badges refresh on the next visit.

**Validation feedback**

- Profile and auth errors render under their fields with `InputError`.
- Avatar errors render under the photo field.
- Composer has no inline error element; a rejected send leaves the text in place (`[KNOWN GAP]`).
- `useFlashToast` is mounted by the root `Toaster` and renders `Inertia::flash` toasts. Friend-action messages use `back()->with('success', …)` and never reach it (`[KNOWN GAP]`).

## 12. Validation, Errors, and Rate Limits

**Input validation**

| Field              | Rules                                                                | Message                                                                 |
| ------------------ | -------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| name               | required, string, max 50                                             | default Laravel message                                                 |
| username           | required, string, min 3, max 20, unique (ignoring the current user)  | default Laravel message                                                 |
| bio                | nullable, string, max 160                                            | default Laravel message                                                 |
| email              | required, string, email, max 255, unique (ignoring the current user) | default Laravel message                                                 |
| avatar             | required, image, jpg/jpeg/png/webp, max 2048                         | "Choose a JPG, PNG, or WebP image up to 2 MB."                          |
| message body       | required, string, max 1000, not only whitespace                      | default Laravel message                                                 |
| search `q`         | nullable, string, max 20                                             | default Laravel message                                                 |
| friend request     | target must exist and not be self                                    | "You cannot send a friend request to yourself." (key `user`)            |

There is no minimum length on name, no lowercase rule on username, and no `%`/`_` rejection. Search trims and escapes wildcards.

**Errors**

| Situation                                   | Result                                                        |
| ------------------------------------------- | ------------------------------------------------------------- |
| Open your own thread                        | 404                                                           |
| Open a stranger's thread                    | 403                                                           |
| Send to yourself                            | 404                                                           |
| Send without friendship                     | 403                                                           |
| Hide someone else's message (third party)   | 403                                                           |
| Accept/decline as the wrong party           | 403                                                           |
| Cancel as the wrong party                   | 403                                                           |
| Remove a non-accepted friendship            | Validation error on `friendship`                              |
| Avatar too large or wrong type              | Field error, current avatar unchanged                         |
| Send validation failure                     | Text stays; no inline message                                 |
| Search failure                              | "Couldn't search right now. Try again."                       |
| Delete failure                              | "Couldn't delete the message. Try again."                     |
| Unexpected server error                     | Laravel's default error response; not a custom page           |

**Rate limits**

| ID   | Where         | Limit        | Key              |
| ---- | ------------- | ------------ | ---------------- |
| RL-1 | Login         | 5 per minute | lowercased email + IP |
| RL-2 | Avatar upload | 3 per hour   | user id or IP    |

Search, profile updates, and friend requests are not rate limited.

**Avatar wait copy:** "Too many photo uploads. Try again in {N seconds}." or "{N minutes}." The submit button is disabled while the countdown runs.

## 13. Security Requirements

- **Authentication:** Fortify session auth. Every chat, friend, search, and settings route requires `auth`. Security and appearance also carry `verified`, but `User` does not implement `MustVerifyEmail` and email verification is disabled, so that middleware does not restrict anyone. Security requires a recent password confirmation. Passkeys are enabled; two-factor is disabled in config.
- **Authorization:** server-side on every action. `MessagePolicy@delete`; `FriendshipService` role checks; pair-scoped message queries; `canMessage` on the thread; channel auth for `user.{id}`.
- **Input and output:** plain text, React escaping, no `dangerouslySetInnerHTML`, LIKE wildcard escaping, 20-character search cap.
- **Mass assignment:** `#[Fillable]` on `User` and `Friendship`; `$fillable` on `Message`. Sender, requester, and addressee always come from the session or the route binding, never from a request body.
- **Files:** avatar validated by content and size, stored under a random name, old file deleted after a successful save.
- **Data exposure:** a hidden body is never sent to the recipient and is not in the broadcast payload. The sender still receives it, so the text is not erased from the database.
- **Rate limits:** login and avatar only.
- **Logging:** broadcast failures are reported. Message bodies are not intentionally written to logs.

## 14. Known Gaps

These are real weaknesses in the shipped app. They are documented instead of being presented as features.

1. **Hide is two-sided, not sender-only.** `MessagePolicy` allows the recipient to hide the message too. The body stays in the database and the sender always receives it; the recipient never does. The draft promised a sender-only delete and no body to the browser.
2. **Unread counts include hidden messages.** `User::conversations()` counts `read_at is null` only, so hiding an unread message does not lower the badge.
3. **Friend-action confirmations never appear.** `useFlashToast` is mounted and renders `Inertia::flash('toast', …)`, so "Profile updated.", "Photo updated.", and "Password updated." do show. `FriendRequestController` uses `back()->with('success', …)` instead, and regular session flash is not part of the Inertia `flash` prop, so sent, accepted, declined, cancelled, and removed confirmations are silent.
4. **Account deletion can fail.** `messages` and `friendships` use `restrictOnDelete`, so deleting a user with any history raises a database error. The settings page still offers the action.
5. **No live badge updates.** Only `chat/show` subscribes to Echo. The chat list and Friends page do not, so badges update on the next request.
6. **The composer hides send errors.** A rejected send leaves the text but shows no message. Enter does not send.
7. **Active status is not presence.** It is based on the last HTTP request, touches at most every 30 seconds, and counts as active for 5 minutes.
8. **No pagination.** All conversations and friends are loaded. Threads show the latest 50 messages, with no way to load older ones.
9. **No disconnected state.** There is no "Reconnecting…" banner.
10. **Queue setting mismatch.** The local `.env` uses `sync`; `.env.example` uses `database`. With `database`, broadcasts need `php artisan queue:work`.
11. **The welcome page is unreachable.** `/` redirects to login or chat, so `resources/js/pages/welcome.tsx` is not used.
12. **The `verified` middleware is a no-op.** Email verification is commented out in `config/fortify.php` and `User` does not implement `MustVerifyEmail`, so "verified" routes are open to any logged-in user.
13. **Two-factor authentication is disabled.** The security page and controller support it, but the Fortify feature is commented out, so only password and passkeys are available.

## 15. Deferred / Not in This Version

These appeared in the earlier draft and are not built:

- Generated usernames at registration (`ana_cruz_4821` style).
- “New chat” dialog with a friend filter and a first-message field.
- Bio in the chat header.
- Typing indicator.
- Real-time friend request, acceptance, and removal events.
- Live sidebar unread badges outside an open thread.
- Rate limits for search, profile updates, and friend requests.
- “New messages” button and the 80 px scroll rule.
- Five-minute message runs and grouped timestamps.
- Message grouping or run-aware spacing.
- Pagination or “load more” for friends, conversations, or older messages.
- “Reconnecting…” banner.
- Read receipts inside the thread beyond the sidebar tick.
- Confirmation toasts for friend actions (profile, photo, and password confirmations already work).
- Dark-mode contrast audit.
- A verified keyboard and 320 px pass recorded in the repo.
- Search result relationship status from the server (it is derived in the browser).
- A `FriendshipPolicy` (authorization lives in the service).
- `CHECK` constraints for self-pairs.
- Messages rate limiting inside an existing thread.
- Group rooms, attachments, reactions, message editing, message search, blocking, reporting, friend suggestions, mutual friends, nicknames, stranger requests, notifications, admin tools.
- Account deletion that preserves or cascades a user's messages safely.

## 16. Testing and Verification

**Run the suite**

```bash
php artisan test --compact
```

Current result: **110 passed, 4 skipped, 516 assertions**.

**Coverage by file**

| File                                    | Covers                                                                 |
| --------------------------------------- | ---------------------------------------------------------------------- |
| `tests/Feature/Auth/*`                  | login, logout, throttle, password reset, confirmation, registration    |
| `tests/Feature/ChatTest.php`            | index, thread access, gating, 50-message limit, ordering, previews, unread clearing, friends without messages |
| `tests/Feature/MessageTest.php`         | friendship gate, self-send, 1000-character limit, authenticated sender |
| `tests/Feature/MessageDeletionTest.php` | both-side hide, recipient visibility, stranger 403, guest redirect, both-channel broadcast |
| `tests/Feature/UserSearchTest.php`      | auth, prefix search, self-exclusion, empty query                        |
| `tests/Feature/FriendRequestTest.php`   | guest access, send, self, accept/decline roles, cancel role, removal   |
| `tests/Feature/FriendshipServiceTest.php` | idempotent send, reverse auto-accept, role checks, remove rules      |
| `tests/Feature/FriendShipQueryTest.php` | friends query, avatar, component casing, pending is not a friend       |
| `tests/Feature/ActiveStatusTest.php`    | 5-minute window, friends page, chat header, conversations              |
| `tests/Feature/Settings/*`              | profile validation and uniqueness, avatar validation and limit, security |
| `tests/Feature/TouchUserLastSeenTest.php` | last-seen write                                                      |

**Checks that are manual:** real-time delivery with Reverb, mobile layout at 320 px, keyboard focus order, dark-mode contrast, and the two-browser friend flow.

## 17. Running the App

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer run dev
```

`composer run dev` starts four processes through `php artisan dev`: the Laravel server, the queue listener, Reverb, and Vite.

To run the processes yourself instead:

```bash
php artisan serve
php artisan reverb:start --debug
php artisan queue:work        # only when QUEUE_CONNECTION=database
npm run dev
```

- The local `.env` uses `QUEUE_CONNECTION=sync`, so broadcasts run immediately and `queue:work` is not needed. `.env.example` uses `database`; if you copy it, start the worker or real-time events will queue and never send.
- The demo seeder creates `john@test` with conversations, plus `ana@test` with a pending request. Password: `password`.
- `npm run build` is required when a frontend change does not appear in the browser. `vite` must be running in development.
