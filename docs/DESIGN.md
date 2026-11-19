# Design: DM Chat App (minimalist)

Companion to `PRD.md` (v1.1). Requirement IDs (FR, BR, NFR) point back to that file. Stack: React, Tailwind CSS, shadcn/ui (PRD AS-8).

## 1. Intent

- **Subject:** private one-to-one conversations between friends.
- **Audience:** you and the friends who test it.
- **Primary job:** agree to connect, then read and write messages with no friction.
- **One idea:** the page is quiet text on a quiet background. Only _your_ messages carry a fill. Everything else is whitespace.
- **The door:** a conversation starts with a friend request, not with a message to a stranger. Relationship state is written in words first and color second.

## 2. Principles

1. **Text first.** No avatars beside messages, no names above them. In a two-person chat, the side (right is you, left is them) says who spoke.
2. **One accent, four jobs.** The teal accent appears only on the primary button, the focus ring, the count badge, and the online dot.
3. **Whitespace instead of boxes.** Rows and sections are separated by space. The only lines in the app are a 1 px divider between the sidebar and the conversation, above the composer, and between profile or Friends page sections.
4. **No decoration.** No gradients, no shadows, no card grids, no icons that repeat a label.
5. **Plain words.** Sentence case, active verbs, and the same name for an action everywhere ("Delete" is "Delete" in the menu and in the dialog).
6. **Relationship before conversation.** A new chat starts with an accepted friend request. Friend state is always text ("Add friend", "Requested", "Friends"), never color alone.
7. **One primary action per section.** On the Friends page the only filled button is "Find people"; row actions are secondary or text buttons.

## 3. Tokens

### Color (light)

All text pairs below meet a 4.5:1 contrast ratio.

| Name    | Hex       | Used for                                                                           |
| ------- | --------- | ---------------------------------------------------------------------------------- |
| Paper   | `#FBFBFC` | Page background                                                                    |
| Surface | `#FFFFFF` | Dialogs, inputs                                                                    |
| Line    | `#E6E8EC` | The three dividers, input borders                                                  |
| Ink     | `#20242B` | Main text                                                                          |
| Quiet   | `#5F6673` | Times, helper text, placeholders, deleted placeholder                              |
| Accent  | `#2F6F6A` | Primary button, focus ring, count badge, online dot                                |
| Mine    | `#E6F0EF` | Fill behind your own messages, hover and selected rows, avatar initials background |
| Error   | `#B42318` | Error text, invalid input border, "Delete" button                                  |

White text on Accent is 5.8:1. Error on Paper is 6.4:1.

### Color (dark) `[stretch, tokens only]`

| Name    | Hex                               |
| ------- | --------------------------------- |
| Paper   | `#14171A`                         |
| Surface | `#1B1F23`                         |
| Line    | `#2B3037`                         |
| Ink     | `#E7E9EC`                         |
| Quiet   | `#9AA3AE`                         |
| Accent  | `#6FB5AE` (text on it: `#0F1A19`) |
| Mine    | `#1F3533`                         |
| Error   | `#F97066`                         |

Check contrast again before building dark mode.

### Type

- **One family:** Instrument Sans (Google Fonts), weights 400, 500, 600. Fallback: `system-ui, -apple-system, "Segoe UI", sans-serif`.
- Sentence case everywhere. No all-caps labels, no small labels above headings.

| Role                                     | Size / line height                                                          | Weight          |
| ---------------------------------------- | --------------------------------------------------------------------------- | --------------- |
| Page title (Profile, Friends)            | 24 / 32                                                                     | 600             |
| Section title, chat name, sidebar title  | 20 / 28 for "Chats" and "Profile"; 16 / 24 for chat name and section titles | 600             |
| Message and input text                   | 16 / 24                                                                     | 400             |
| UI text (labels, buttons, sidebar names) | 14 / 20                                                                     | 500             |
| Meta (times, helper text, badge)         | 12 / 16 for times and badge; 14 / 20 for helper text                        | 400 (badge 600) |

Messages are 16 px so mobile browsers do not zoom in when the composer is focused. Message width is capped at 70% of the pane and 65 characters.

### Space and shape

- **Spacing scale (px):** 4, 8, 12, 16, 24, 32, 48.
- **Between messages in a run:** 4. **Between runs:** 16. **Between profile sections:** 48.
- **Radius:** 6 px for inputs and buttons, 14 px for message blocks, full circle for avatars and badges.
- **Elevation:** none. Dialogs use only shadcn's default overlay.
- **Borders:** 1 px Line, only where principle 3 allows.

### Motion

- New message: opacity 0 to 1 over 120 ms. No sliding.
- Delete: the text cross-fades to "This message was deleted" over 150 ms.
- Dialogs: shadcn default open and close.
- Nothing animates on page load. With reduced motion on, all of the above are instant.

## 4. Layout

Everything is left-aligned except your own messages, which sit on the right.

**Desktop (768 px and up)**

```
┌──────────────────┬─────────────────────────────────────────┐
│ Chats   New chat │ (av) Ana Cruz  @ana_cruz_4821           │
│                  │      Bio line, one row, truncated       │
│ Friends        2 │─────────────────────────────────────────│
│                  │                                         │
│ (av) Ben Reyes 2 │  Hey, are you free later?               │
│ (av) Ana Cruz    │                              ┌────────┐ │
│ (av) Carl Dizon  │                              │ Yes!   │ │
│                  │                              └────────┘ │
│                  │                                  14:02  │
│                  │  This message was deleted               │
│                  │                                         │
│ (av) You         │─────────────────────────────────────────│
│                  │  Write a message                  Send  │
└──────────────────┴─────────────────────────────────────────┘
   sidebar 300 px            conversation pane (flexible)
```

**Mobile (under 768 px), one screen at a time**

```
┌──────────────────────┐      ┌──────────────────────┐
│ Chats      New chat  │      │ ‹ Chats              │
│                      │      │ (av) Ana Cruz        │
│ Friends           2  │      │ @ana_cruz_4821       │
│                      │      │                      │
│ (av) Ben Reyes     2 │      │                      │
│ (av) Ana Cruz        │      │──────────────────────│
│ (av) Carl Dizon      │      │ Hey, are you free?   │
│                      │      │         ┌──────────┐ │
│                      │      │         │ Yes!     │ │
│ (av) You             │      │         └──────────┘ │
└──────────────────────┘      │──────────────────────│
                              │ Write a message Send │
                              └──────────────────────┘
```

**Profile page:** one centered column, 480 px wide at most, on both screen sizes.

```
 Profile

 Details
 Name        [______________________]
 Username    [______________________]
             Lowercase letters, numbers, and underscores.
 Bio         [______________________]   0/160
 [ Save changes ]

 ─────────────────────────────────────  (48 px of space around the line)

 Photo
 ( 96 px avatar )   [ Choose image ]
 [ Save photo ]
```

**Friends page:** one centered column, 480 px wide at most, inside the chat sidebar's content pane on desktop and full screen on mobile.

```
 Friends                                  [ Find people ]

 Requests
 (av) Ben Reyes    @ben_reyes_2041        [ Accept ]  Decline

 Sent
 (av) Carl Dizon   @carl_dizon_9182       Cancel request

 ──────────────────────────────────────────────────────

 Your friends
 (av) Ana Cruz     @ana_cruz_4821         Remove
 (av) Ben Reyes    @ben_reyes_2041        Remove

 Empty: No friend requests. / No sent requests. / No friends yet. Find people to add.
```

## 5. Components

**Sidebar**

- Header: "Chats" (20/28, 600) on the left, "New chat" as a text button on the right.
- Friends row: "Friends" (14/20, 500) with the request count badge on the right, above the chat list. Row padding 12 / 16. Hover and the open page use the Mine fill. It links to `/friends`.
- Chat row: 40 px avatar, name (14/20, 500), unread badge on the right. Row padding 12 / 16. Hover and the open chat use the Mine fill. A chat with unread messages shows its name in 600 weight.
- Footer: your avatar and name. It opens a menu with "Edit profile" and "Log out".
- Empty: "No chats yet. Find a friend to start."

**Avatar**

- Image, circle. If none, show 1 to 2 initials in Accent on a Mine background.
- Sizes: 40 (sidebar and friends rows), 32 (chat header), 96 (profile).

**Count badge**

- Accent pill, at least 20 px wide, white text 12/16 600. Shows "9+" above 9. Hidden at 0. Used for unread chats and incoming friend requests.

**Online dot (stretch)**

- 8 px Accent circle at the bottom right of the avatar.

**Friends page**

- Title "Friends" (24/32, 600) on the left, "Find people" as the only primary button on the right.
- Three sections in order: "Requests", "Sent", "Your friends". Section headings are 16/24, 600, with 8 px below and 32 px between sections. One 1 px Line separates "Sent" from "Your friends".
- Rows are 40 px high with 12 px vertical padding: 40 px avatar, name (14/20, 500), @username (12/16, Quiet), actions on the right.
- Requests row: "Accept" as a secondary button, "Decline" as a text button. Sent row: "Cancel request" as a text button. Friends row: "Remove" as a text button in Quiet.
- Empty states sit left-aligned in Quiet: "No friend requests.", "No sent requests.", "No friends yet. Find people to add."
- On mobile the page is full screen with the same sections; actions wrap below the name if the row would overflow.

**Find people dialog (shadcn Dialog)**

- Title "Find people". Field "Username" with results directly under it (avatar, name, @username, relationship action).
- Actions: "Add friend" secondary, "Requested" disabled Quiet text, "Accept" secondary, "Friends" Quiet text.
- Loading "Loading…"; no match "No one found."; failure "Request not sent. Try again." under the field.
- Footer: "Close" text button. The result actions are not primary buttons, so the dialog never has more than one filled control.

**Remove friend dialog (shadcn AlertDialog)**

- Title "Remove {Name}?" Text "They'll be removed from your friends list. Your chat history stays." Buttons "Cancel" and "Remove". "Remove" uses the Error fill with white text. Focus starts on "Cancel".

**Chat header**

- 32 px avatar, name (16/24, 600), @username in Quiet (14/20), bio below in Quiet on one truncated line, shown only if set.
- On mobile, a "‹ Chats" back control sits above it. Use a chevron icon plus the word.
- Typing text (stretch) replaces the bio line: "{Name} is typing…" in Quiet.

**Message**

- Yours: right side, Mine fill, Ink text, 14 px radius, 12 / 16 padding.
- Theirs: left side, no fill, no border, Ink text.
- Deleted: same side, no fill, "This message was deleted" in Quiet italic. No menu.
- Time (12/16, Quiet) sits under the last message of a run (same person, within 5 minutes). Every message also carries its time in a `<time>` element for screen readers.
- Date line: centered, Quiet 12/16, with text only (no lines): "Today", "Yesterday", or "Mon 9 Nov".
- Options menu (your messages only): a small "⋯" button labeled "Message options", shown on hover and focus on desktop and always visible (at lower opacity) on touch screens. One item: "Delete".

**Composer**

- Sits above a 1 px Line. Textarea starts at 1 row and grows to 5, then scrolls. Placeholder "Write a message". Send button on the right, labeled "Send".
- Enter sends. Shift+Enter adds a line. "Send" is disabled when the box is empty or only spaces, or while sending. Focus stays in the box after sending.
- Failure text under the box in Error: "Message not sent. Try again."

**"New messages" button**

- A text button in Accent, centered above the composer, shown only when you have scrolled up more than 80 px and a message arrives.

**New chat dialog (shadcn Dialog)**

- Title "New chat". Field "Search friends" filters the accepted friends listed directly under it (avatar, name, @username). Field "Message" (textarea). Buttons "Cancel" and "Start chat".
- No friends yet: "No friends yet. Find people to add." with a "Find people" text button that opens the Find people dialog.
- Filter with no match: "No friends found." Waiting: "Loading…" in Quiet.

**Delete dialog (shadcn AlertDialog)**

- Title "Delete this message?" Text "This can't be undone." Buttons "Cancel" and "Delete". "Delete" uses the Error fill with white text. Focus starts on "Cancel".

**Buttons**

- Primary: Accent fill, white text, 40 px high, 6 px radius, weight 500. One primary button per screen or dialog.
- Secondary: no fill, Ink text, 1 px Line border.
- Text button: no fill, no border, Ink or Accent text.

**Form fields**

- Label above (14/20, 500). Input 40 px high, Surface fill, 1 px Line border, 6 px radius. Helper text in Quiet under the field. On error the border turns Error and the message appears under the field in Error (14/20).
- Bio shows a live counter "0/160" in Quiet on the right.

**Rate limit notice**

- Appears directly under the submit button in Error (14/20) with the message from PRD section 12. The submit button is disabled and its label counts down ("Try again in 0:42"), then returns to its normal label. Typed values stay.

**Toasts (Sonner)**

- Bottom center, Surface fill, Ink text, 14/20. Used only for "Profile saved.", "Photo updated.", "Friend request sent.", "You're now friends with {Name}.", "Friend request cancelled.", and "{Name} removed from your friends."

**Banners**

- "Reconnecting…" is one line of Quiet text at the top of the conversation pane, with no fill and no icon.

## 6. Copy deck

| Where                                     | Text                                                                                         |
| ----------------------------------------- | -------------------------------------------------------------------------------------------- |
| Sidebar title                             | Chats                                                                                        |
| Sidebar button                            | New chat                                                                                     |
| Sidebar friends row                       | Friends                                                                                      |
| Sidebar empty                             | No chats yet. Find a friend to start.                                                        |
| Sidebar footer menu                       | Edit profile / Log out                                                                       |
| New chat dialog                           | New chat / Search friends / Message / Cancel / Start chat                                    |
| New chat empty                            | No friends yet. Find people to add.                                                          |
| New chat no match                         | No friends found.                                                                            |
| Friends page                              | Friends / Find people / Requests / Sent / Your friends / Accept / Decline / Cancel request / Remove |
| Friends empty states                      | No friend requests. / No sent requests. / No friends yet. Find people to add.                |
| Find people dialog                        | Find people / Username / Add friend / Requested / Accept / Friends / Close                   |
| Find people loading                       | Loading…                                                                                     |
| Find people no match                      | No one found.                                                                                |
| Find people failure                       | Request not sent. Try again.                                                                 |
| Remove friend dialog                      | Remove {Name}? / They'll be removed from your friends list. Your chat history stays. / Cancel / Remove |
| Search loading                            | Loading…                                                                                     |
| Search no match                           | No one found.                                                                                |
| Conversation empty                        | No messages yet. Say hi.                                                                     |
| Composer placeholder                      | Write a message                                                                              |
| Send button                               | Send                                                                                         |
| New messages button                       | New messages                                                                                 |
| Message options trigger (accessible name) | Message options                                                                              |
| Menu item and dialog action               | Delete                                                                                       |
| Delete dialog                             | Delete this message? / This can't be undone. / Cancel / Delete                               |
| Deleted message                           | This message was deleted                                                                     |
| Date lines                                | Today / Yesterday / Mon 9 Nov                                                                |
| Profile page                              | Profile / Details / Name / Username / Bio / Save changes / Photo / Choose image / Save photo |
| Username helper                           | Lowercase letters, numbers, and underscores.                                                 |
| Toasts                                    | Profile saved. / Photo updated. / Friend request sent. / You're now friends with {Name}. / Friend request cancelled. / {Name} removed from your friends. |
| Send failed                               | Message not sent. Try again.                                                                 |
| Friend request failed                     | Request not sent. Try again.                                                                 |
| Friend action unavailable                 | This request is no longer available.                                                         |
| Empty message                             | Write a message first.                                                                       |
| Disconnected                              | Reconnecting…                                                                                |
| Page-level failure                        | Something went wrong. Try again.                                                             |
| Forbidden                                 | You can't do that.                                                                           |
| Validation and rate limit messages        | Exactly as written in PRD section 12                                                         |

## 7. States

| State              | What the user sees                                                                                                                                   |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Loading            | The words "Loading…" in Quiet. No spinner, no shimmer.                                                                                               |
| Empty              | The sentence from the copy deck, in Quiet, left-aligned at the top of the empty area, with the relevant action below it ("New chat" or "Find people"). |
| Failed             | A message in Error directly under the control that failed. Typed text is kept.                                                                       |
| Disconnected       | "Reconnecting…" at the top of the conversation pane. Existing messages stay visible.                                                                 |
| Rate limited       | The notice and the disabled, counting-down button described in section 5.                                                                            |
| Requested          | The result action reads "Requested" in Quiet and is disabled; the Sent section lists the person with "Cancel request".                               |
| Request received   | The Friends row badge shows the count; the Requests section shows "Accept" and "Decline".                                                             |
| Request accepted   | The row leaves Requests, the person appears under Your friends and in New chat, and the toast names them.                                            |
| Request unavailable | "This request is no longer available." in Error, and the section refreshes from the server.                                                         |
| Friend removed     | The row leaves Your friends and New chat; the existing chat and messages stay.                                                                        |

## 8. Accessibility

- Every control works with the keyboard. Focus shows as a 2 px Accent ring with a 2 px offset on every `:focus-visible`.
- Dialogs trap focus, close on Escape, and return focus to the button that opened them.
- The message list is a polite live region. New messages and "This message was deleted" are announced as text.
- The Friends badge and friend request changes are announced politely. Request action labels include the person's name, for example "Accept friend request from Ana Cruz" and "Remove Ana Cruz from your friends".
- Field errors are linked to their fields so screen readers read them.
- Color is never the only signal: errors have text, unread has a number, friend state is written as "Add friend", "Requested", "Accept", or "Friends", and your messages are also on the right.
- Touch targets are at least 40 px high. Text pairs meet 4.5:1 (section 3).
- The remove-friend dialog starts on "Cancel" and returns focus to the "Remove" trigger when closed.
- Reduced motion turns off every animation in section 3.

## 9. shadcn/ui mapping

Define the tokens as CSS variables in the app stylesheet and map them to shadcn's theme variables:

| shadcn variable                                                           | Our token |
| ------------------------------------------------------------------------- | --------- |
| `--background`                                                            | Paper     |
| `--foreground`                                                            | Ink       |
| `--card`, `--popover`                                                     | Surface   |
| `--card-foreground`, `--popover-foreground`                               | Ink       |
| `--primary`, `--ring`                                                     | Accent    |
| `--primary-foreground`                                                    | `#FFFFFF` |
| `--secondary`, `--muted`, `--accent` (shadcn's hover/selected background) | Mine      |
| `--secondary-foreground`, `--accent-foreground`                           | Ink       |
| `--muted-foreground`                                                      | Quiet     |
| `--border`, `--input`                                                     | Line      |
| `--destructive`                                                           | Error     |
| `--radius`                                                                | 6 px      |

The 14 px message radius is the one custom value outside shadcn's `--radius`. Components used: Button, Input, Textarea, Label, Avatar, Dialog, AlertDialog, DropdownMenu, Sonner.

## 10. Choices made on purpose

- No cream or warm-clay palette, and no black with a neon accent. The palette is cool white, slate ink, and one muted teal.
- No bubble tails, gradients, shadows, or card grids.
- No avatars or names inside the message list, because there are only two people.
- Your messages are the only filled shape in the app. That is the one memorable thing, and everything near it stays quiet.
- Friendship is a gate, not a badge. Requests live on one Friends page instead of being mixed into the chat list, so unread messages and relationship state never compete for the same row.
- Accept and Decline are quiet row actions; "Find people" is the only primary button on the page. A list of filled buttons would look like a dashboard.
- Removing a friend is not blocking. The row leaves the list, but the existing conversation stays, because the product has no moderation model in v1.

## 11. Not covered here

Rooms, group avatars, reactions, attachments, message search, friend suggestions, mutual friends, blocking, and stranger message requests are out of scope for v1 (PRD section 17). Login and register pages keep the starter kit's layout and only take the colors, font, and button styles from this file.
