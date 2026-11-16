# Design: DM Chat App (minimalist)

Companion to `PRD-dm-chat-app.md`. Requirement IDs (FR, BR, NFR) point back to that file. Stack assumed: React, Tailwind CSS, shadcn/ui (PRD AS-8).

## 1. Intent

- **Subject:** private one-to-one conversations.
- **Audience:** you and the friends who test it.
- **Primary job:** read and write messages with no friction.
- **One idea:** the page is quiet text on a quiet background. Only _your_ messages carry a fill. Everything else is whitespace.

## 2. Principles

1. **Text first.** No avatars beside messages, no names above them. In a two-person chat, the side (right is you, left is them) says who spoke.
2. **One accent, four jobs.** The teal accent appears only on the primary button, the focus ring, the unread badge, and the online dot.
3. **Whitespace instead of boxes.** Rows and sections are separated by space. The only line in the app is a 1 px divider between the sidebar and the conversation, above the composer, and between profile sections.
4. **No decoration.** No gradients, no shadows, no card grids, no icons that repeat a label.
5. **Plain words.** Sentence case, active verbs, and the same name for an action everywhere ("Delete" is "Delete" in the menu and in the dialog).

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
| Accent  | `#2F6F6A` | Primary button, focus ring, unread badge, online dot                               |
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
| Page title (Profile)                     | 24 / 32                                                                     | 600             |
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
│ (av) Ben Reyes 2 │─────────────────────────────────────────│
│ (av) Ana Cruz    │                                         │
│ (av) Carl Dizon  │  Hey, are you free later?               │
│                  │                              ┌────────┐ │
│                  │                              │ Yes!   │ │
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
│ (av) Ben Reyes     2 │      │ @ana_cruz_4821       │
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

## 5. Components

**Sidebar**

- Header: "Chats" (20/28, 600) on the left, "New chat" as a text button on the right.
- Row: 40 px avatar, name (14/20, 500), unread badge on the right. Row padding 12 / 16. Hover and the open chat use the Mine fill. A chat with unread messages shows its name in 600 weight.
- Footer: your avatar and name. It opens a menu with "Edit profile" and "Log out".
- Empty: "No chats yet. Search for someone to start."

**Avatar**

- Image, circle. If none, show 1 to 2 initials in Accent on a Mine background.
- Sizes: 40 (sidebar), 32 (chat header), 96 (profile).

**Unread badge**

- Accent pill, at least 20 px wide, white text 12/16 600. Shows "9+" above 9. Hidden at 0.

**Online dot (stretch)**

- 8 px Accent circle at the bottom right of the avatar.

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

- Title "New chat". Field "Username" with results listed directly under it (avatar, name, @username, up to 10). Field "Message" (textarea). Buttons "Cancel" and "Start chat".
- Search with no match: "No one found." Waiting: "Loading…" in Quiet.

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

- Bottom center, Surface fill, Ink text, 14/20. Used only for "Profile saved." and "Photo updated."

**Banners**

- "Reconnecting…" is one line of Quiet text at the top of the conversation pane, with no fill and no icon.

## 6. Copy deck

| Where                                     | Text                                                                                         |
| ----------------------------------------- | -------------------------------------------------------------------------------------------- |
| Sidebar title                             | Chats                                                                                        |
| Sidebar button                            | New chat                                                                                     |
| Sidebar empty                             | No chats yet. Search for someone to start.                                                   |
| Sidebar footer menu                       | Edit profile / Log out                                                                       |
| New chat dialog                           | New chat / Username / Message / Cancel / Start chat                                          |
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
| Toasts                                    | Profile saved. / Photo updated.                                                              |
| Send failed                               | Message not sent. Try again.                                                                 |
| Empty message                             | Write a message first.                                                                       |
| Disconnected                              | Reconnecting…                                                                                |
| Page-level failure                        | Something went wrong. Try again.                                                             |
| Forbidden                                 | You can't do that.                                                                           |
| Validation and rate limit messages        | Exactly as written in PRD section 12                                                         |

## 7. States

| State        | What the user sees                                                                                                                                   |
| ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| Loading      | The words "Loading…" in Quiet. No spinner, no shimmer.                                                                                               |
| Empty        | The sentence from the copy deck, in Quiet, left-aligned at the top of the empty area, with the "New chat" button below it when the sidebar is empty. |
| Failed       | A message in Error directly under the control that failed. Typed text is kept.                                                                       |
| Disconnected | "Reconnecting…" at the top of the conversation pane. Existing messages stay visible.                                                                 |
| Rate limited | The notice and the disabled, counting-down button described in section 5.                                                                            |

## 8. Accessibility

- Every control works with the keyboard. Focus shows as a 2 px Accent ring with a 2 px offset on every `:focus-visible`.
- Dialogs trap focus, close on Escape, and return focus to the button that opened them.
- The message list is a polite live region. New messages and "This message was deleted" are announced as text.
- Field errors are linked to their fields so screen readers read them.
- Color is never the only signal: errors have text, unread has a number, and your messages are also on the right.
- Touch targets are at least 40 px high. Text pairs meet 4.5:1 (section 3).
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

## 11. Not covered here

Rooms, group avatars, reactions, attachments, and message search are out of scope for v1 (PRD section 17). Login and register pages keep the starter kit's layout and only take the colors, font, and button styles from this file.
