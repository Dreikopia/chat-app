# Design: DM Chat App (as built)

Companion to `PRD.md` v2.0. Requirement IDs point back to that file. Stack: React 19, Tailwind CSS 4, shadcn/ui, Laravel Inertia 3.

This document describes the screens that exist. Unbuilt ideas are listed in section 12.

## 1. Intent

- **Subject:** private one-to-one conversations between accepted friends.
- **Audience:** you and the friends who test it.
- **Primary job:** agree to connect, then read and write messages with no friction.
- **One idea:** quiet text on a quiet background. Only your own messages carry a fill. Everything else is whitespace.
- **The door:** a conversation starts with a friend request, not with a message to a stranger. Relationship state is written in words first and colour second.

## 2. Principles

1. **Text first.** No avatars beside messages and no names above them. The side says who spoke: yours on the right, theirs on the left.
2. **One accent.** Teal marks the primary button, focus ring, unread badge, own-message fill, selected rows, and the mobile active tab. The shared `ActiveStatus` dot is green; the mobile list and active strip draw their own teal dots.
3. **Whitespace instead of boxes.** Rows and sections are separated by space. Lines are used for the sidebar edge, the chat header, the composer, the mobile tab bar, and section breaks.
4. **No decoration.** No gradients, no shadows, no card grids, no icons that repeat a label.
5. **Plain words.** Sentence case, active verbs, the same name for an action everywhere.
6. **Relationship before conversation.** Sending requires an accepted friend request. Friend state is always text ("Friends", "Request sent", "Accept", "Add"), never colour alone.
7. **One primary action per section.** On Friends, the only filled button is "Find people". Row actions are outline or text buttons.

## 3. Tokens

Defined in `resources/css/app.css`. Both palettes are built. The app starts in `system` mode and Settings → Appearance switches between Light, Dark, and System.

### Light

| Name    | Hex       | Used for                                                                 |
| ------- | --------- | ------------------------------------------------------------------------ |
| Paper   | `#FBFBFC` | Page and sidebar background                                              |
| Surface | `#FFFFFF` | Dialogs, inputs, popovers, mobile tab bar                                |
| Line    | `#E6E8EC` | Sidebar edge, header border, composer border, input borders              |
| Ink     | `#20242B` | Main text                                                                |
| Quiet   | `#5F6673` | Times, helper text, placeholders, deleted placeholder, secondary labels  |
| Accent  | `#2F6F6A` | Primary button, focus ring, unread badge, own-message fill, active tab   |
| Mine    | `#E6F0EF` | Other person's message fill, hover and selected rows, avatar initials    |
| Error   | `#B42318` | Error text, invalid input border, destructive buttons                    |
| Active  | `#22C55E` | Online dot (`green-500`)                                                 |

White text on Accent is 5.8:1. Error on Paper is 6.4:1.

### Dark

| Name    | Hex       |
| ------- | --------- |
| Paper   | `#14171A` |
| Surface | `#1B1F23` |
| Line    | `#2B3037` |
| Ink     | `#E7E9EC` |
| Quiet   | `#9AA3AE` |
| Accent  | `#6FB5AE` (text on it: `#0F1A19`) |
| Mine    | `#1F3533` |
| Error   | `#F97066` |

Dark mode is reachable from Settings → Appearance: Light, Dark, System. `useAppearance` stores the choice in `localStorage` and in an `appearance` cookie; `app.blade.php` reads the cookie to set the initial `dark` class before hydration.

### Type

- **One family:** Instrument Sans, weights 400, 500, 600. It is self-hosted through `laravel-vite-plugin/fonts` (Bunny) and emitted by the `@fonts` directive in `resources/views/app.blade.php`. Fallback: `system-ui, -apple-system, "Segoe UI", sans-serif`.
- Sentence case everywhere. No all-caps labels.

| Role                                      | Actual classes                          | Size / line height |
| ----------------------------------------- | --------------------------------------- | ------------------ |
| Chat list title (mobile)                  | `text-xl font-semibold`                 | 20 / 28            |
| Friends page title                        | `text-xl md:text-2xl font-semibold`     | 20 / 28 → 24 / 32  |
| Settings layout heading                   | `text-2xl font-semibold`                | 24 / 32            |
| Section title                             | `text-base font-semibold`               | 16 / 24            |
| Chat name in header                       | `font-medium`                           | 16 / 24            |
| Message text                              | `text-sm`                               | 14 / 20            |
| Input and textarea text                   | `text-base`                             | 16 / 24            |
| UI text (labels, buttons, rows)           | `text-sm`                               | 14 / 20            |
| Meta (times, dates, badges)               | `text-[11px]`, `text-xs`                | 11 / 16, 12 / 16   |

Message bubbles are 14 px (`text-sm`). The shadcn `Input` and `Textarea` primitives are 16 px (`text-base`), so the composer and the profile fields render at 16 px. The composer is a two-row textarea.

### Space and shape

- **Spacing scale:** 4, 8, 12, 16, 24, 32, 40, 48.
- **Between messages:** `gap-2` (8 px). Messages are not grouped into runs.
- **Message shape:** `rounded-2xl` (16 px), `px-3 py-2`, max width 75% of the pane.
- **Inputs and buttons:** 6 px radius (`--radius`), 40 px high.
- **Avatars:** full circle. 32 px in the chat header, 40 px in rows, 48 px in the mobile chat list, 96 px on the profile.
- **Elevation:** none. Dialogs use only shadcn's overlay.
- **Borders:** 1 px Line where principle 3 allows.

### Motion

- Two keyframes are defined in `resources/css/app.css`: `message-in` (opacity 0 to 1 over 120 ms) and `message-delete` (opacity 1 to 0 over 150 ms).
- No component applies them. Message bubbles appear and disappear without animation. Dialogs use shadcn's default open and close. Nothing animates on page load.
- Reduced-motion handling is a target, not an audited setting.

## 4. Layout

### Desktop, 768 px and up

```
┌────────────────────┬──────────────────────────────────────────┐
│ Chats              │ (av) Sara Kim             ●              │
│                    │ @sarakim                                 │
│ Friends         2  │──────────────────────────────────────────│
│                    │                                          │
│ Search…            │   Morning! Did you see the new mockups?  │
│ Direct Messages    │                                          │
│ (av) Sara Kim      │                       ┌────────────────┐ │
│      @sarakim  2   │                       │ Yes, looks good│ │
│ (av) Jane Cooper   │                       └────────────────┘ │
│      @janecooper   │                              14:02       │
│                    │   This message was deleted               │
│ (av) You           │                                          │
│      john@test     │──────────────────────────────────────────│
│                    │  [ Write a message… ]            [Send]  │
└────────────────────┴──────────────────────────────────────────┘
   sidebar (shadcn 16rem)        conversation pane (flexible)
```

- The sidebar is `ChatSidebar`: shadcn `Sidebar` with `variant="inset"` and `collapsible="offcanvas"`.
- Header: "Chats" (`text-lg font-semibold`). Below it, a Friends row with a `Users` icon and the pending count badge.
- Conversation list: a search input with placeholder "Search...", a "Direct Messages" heading, then rows.
- Row: 40 px avatar with active dot, name, `@username`, unread badge. No preview in the narrow sidebar.
- Footer: your avatar, name, and email in a menu with "Settings" and "Log out".
- Empty sidebar: "No chats yet. Search for someone to start."

### Mobile, below 768 px

```
┌──────────────────────────┐      ┌──────────────────────────┐
│ Chats               (av) │      │ ‹  (av) Sara Kim     ●   │
│                          │      │    @sarakim              │
│ [ Search chats or people]│      │──────────────────────────│
│                          │      │ Morning! Did you see…    │
│ Active friends    1 online│     │            ┌───────────┐ │
│ (av) (av) (av)           │      │            │ Yes!      │ │
│                          │      │            └───────────┘ │
│ [All] [Unread]           │      │                  14:02   │
│ (av) Sara Kim      14:02 │      │──────────────────────────│
│      @sarakim       2    │      │ [ Write a message… ] Send│
│ (av) Jane Cooper  Mon    │      └──────────────────────────┘
│ (av) Mike Ross    Nov 12 │
│                          │
│ Chats  Friends  Profile  │
└──────────────────────────┘
```

- **Chat index (`/chat`):** a mobile-only list (`md:hidden`). 64 px header with "Chats" and the account avatar menu. Search field "Search chats or people". An "Active friends" horizontal strip appears when at least one conversation partner is active, with the count and first names. All/Unread filter pills.
- **Chat row:** minimum 72 px high, 48 px avatar with active dot, name (semibold when unread), `@username`, relative time, last-message preview, read/sent ticks, unread badge capped at "9+".
- **Chat empty state on desktop:** "Select a conversation to start chatting." centred in the pane, hidden on mobile.
- **Thread (`/chat/{user}`):** a 64 px header with a chevron-left back control (`md:hidden`), 32 px avatar, name, `@username`, and active dot. No bio.
- **Tab bar:** fixed bottom, 64 px, border-top, Surface fill. Chats, Friends, Profile. Icons 24 px, labels 12 px. The active tab is Accent. Badges cap at "9+". Hidden on `/chat/{user}`.
- Chat list and Friends pages add bottom padding for the tab bar plus the safe-area inset.

### Friends page

One centered column, `max-w-2xl`, inside the chat layout. Full width on mobile.

```
 Friends                              [ Find people ]

 Friend requests                         2 pending
 (av) Ben Reyes   @benreyes        [Accept]  Decline

 Sent requests                               1 sent
 (av) Carl Dizon  @carldizon       Cancel request

 ──────────────────────────────────────────────────────

 Your friends                            3 friends
 (av) Ana Cruz    @anacruz         Remove
 (av) Ben Reyes   @benreyes        Remove

 Empty: You haven't added any friends yet.
```

- Title row: "Friends" on the left, "Find people" primary button on the right.
- Sections are `mt-10`. Headings are `text-base font-semibold` with a right-aligned count: "{n} pending", "{n} sent", "{n} friend(s)".
- Request sections render only when non-empty. "Your friends" always renders and has the empty line above.
- A 1 px Line and `pt-8` separate "Your friends" from the request sections when requests exist.
- Rows: 40 px avatar, name, `@username`, active dot next to the name, actions on the right, divided by `divide-y`.
- Incoming: "Accept" outline button, "Decline" ghost button.
- Outgoing: "Cancel request" ghost button.
- Friend: "Remove" ghost button that turns destructive on hover.

### Settings

The settings pages use the app sidebar and a settings sub-layout.

```
 Settings
 Manage your profile and account settings

 Profile      Profile
 Security     Update your profile information
 Appearance   Name / Username / Bio / Email
              [ Save ]
              ─────────────────────────────
              Photo
              ( 96 px avatar ) [ Choose image ]
              [ Save photo ]
              ─────────────────────────────
              Delete account
              Warning: Please proceed with caution...
              [ Delete account ]
```

- Side navigation: Profile, Security, Appearance. The current item uses the `bg-muted` fill.
- Content column: `max-w-2xl`; the inner profile form is `max-w-xl`.
- Profile: heading "Profile", description "Update your profile information". Fields Name, Username, Bio, Email. Button "Save".
- Photo: heading "Photo", description "Update your profile picture". 96 px preview, "Choose image", "Save photo". The upload field is visually hidden behind the label.
- Delete account: heading "Delete account", a destructive-tinted warning box, and a destructive "Delete account" button that opens a password-confirm dialog.
- Security: password form and passkeys. The two-factor section is hidden because the Fortify feature is disabled.
- Appearance: heading "Appearance settings", description "Update the appearance settings for your account", then Light / Dark / System tabs with sun, moon, and monitor icons.

## 5. Components

### Chat sidebar

- `SidebarHeader` with "Chats".
- Friends `SidebarMenuButton` with `Users` icon, the label "Friends", and a `SidebarMenuBadge`. Badge shows the incoming pending count, capped at "99+".
- Search input with placeholder "Search...".
- "Direct Messages" heading.
- Conversation rows with active state from `activeId`.
- Footer menu with Settings and Log out.
- Empty: "No chats yet. Search for someone to start."

### Conversation row

- 40 px avatar with a 10 px active dot in the bottom-right and a ring matching the sidebar.
- Name at `text-sm font-medium`.
- `@username` at `text-xs text-muted-foreground`.
- Unread badge: primary pill, minimum 20 px, text `text-[10px]`, capped at "99+".
- Active row uses `bg-sidebar-accent`.

### Mobile chat row

- Minimum 72 px.
- 48 px avatar with a 12 px active dot ringed with Paper.
- Name and `@username` on the first line, relative time on the right.
- Second line: last-message preview with a sent tick (`Check`) or read tick (`CheckCheck`), and an unread badge capped at "9+".
- Preview rules: a hidden message from the partner shows "This message was deleted"; a friend with no messages shows "You're now friends with {Name}.".
- Time: 24-hour `HH:mm` today (the mobile list forces `hour12: false`), "Yesterday", a short weekday within the week, otherwise "Mon D".

### Active status

- The shared `ActiveStatus` renders a `size-2` green (`#22C55E`) circle with `aria-label="Active"` and `title="Active"`.
- Rendered in conversation rows, the chat header, and Friends rows.
- The mobile chat list and the active strip bypass the component and draw a 12 px teal (`bg-primary`) dot ringed with Paper.
- The value refreshes on the next request. It is not live.

### Avatar

- Image in a circle. Without an image, 1 to 2 initials on a Mine background in Accent.
- Sizes: 32 (header), 40 (rows), 48 (mobile chat rows), 96 (profile).
- The sidebar footer and user menu use the starter-kit avatar with initials on a neutral background.

### Count badge

- Accent pill, white text in light mode, 18 to 20 px high.
- Sidebar conversation badge caps at "99+"; the mobile chat list and tab bar cap at "9+".
- Hidden at 0.

### Chat header

- 64 px high, 1 px bottom border.
- Desktop: sidebar trigger on the left.
- Mobile: chevron-left back control labelled "Back to chats".
- 32 px avatar, name, `@username`, active dot.
- No bio, no typing text.

### Message list

- Scroll container with `gap-2 p-4`, newest at the bottom.
- Own messages right, partner's left.
- Every message has a time underneath in `text-[11px] text-muted-foreground`, formatted with `Intl.DateTimeFormat` in the browser locale (for example "2:02 PM"), with a full date/time in the `title` attribute.
- Date line: centered, `text-[11px] font-medium`, "Today", "Yesterday", a long weekday within 7 days, or a full date such as "November 9, 2026".
- The list scrolls to the bottom whenever the last message id changes. There is no "New messages" button and no 80 px rule.
- Empty: "No messages yet. Say hello."

### Message

- Own: right side, `bg-primary`, white/`primary-foreground` text, `rounded-2xl`, `px-3 py-2`, `text-sm`, max 75% width.
- Theirs: left side, `bg-muted` (Mine in light mode), Ink text, same shape.
- Hidden: the body is `null`; the bubble keeps its side and fill but shows "This message was deleted" in italic, at 70% opacity on your own fill or Quiet on theirs.
- No grouping, no avatars, no sender names.
- Options menu: a "⋯" button with accessible name "Message options". It is `opacity-0` by default and appears on hover or focus. It appears on **both** own and partner messages while the message is visible. One item: "Delete message".
- There is no always-visible touch state; visibility depends on hover or focus, which is unreliable on touch screens.
- The menu is hidden for an already hidden message.

### Composer

- A form with a 1 px top border and `p-4`.
- Two-row textarea, placeholder "Write a message...".
- "Send" button on the right.
- "Send" is disabled when the value is empty or only whitespace, and while processing.
- Enter inserts a newline. Only the button submits.
- Focus does not move after sending; the page redirect re-renders the form.
- No inline error text. A failed send leaves the text in place and shows no message.

### Delete message dialog (shadcn AlertDialog)

- Title "Delete message".
- Own message: "This message will be hidden from the other person. You'll still see it."
- Partner's message: "This message will be removed from your view. The other person will still see it."
- Buttons "Cancel" and "Delete" (default primary style). While running, the action reads "Deleting...".
- Failure text inside the dialog: "Couldn't delete the message. Try again."

### Find people dialog (shadcn Dialog)

- `sm:max-w-md`. Title "Find people", description "Search by username and send a friend request."
- Search input with a magnifier icon and the accessible label "Search by username", max 20 characters, autofocus.
- A spinner appears inside the field while searching.
- Content area `min-h-32`:
  - Under 2 characters: "Type at least 2 characters to search."
  - Searching with no results yet: "Searching..."
  - No match: "No users found for @{term}."
  - Failure: "Couldn't search right now. Try again." in Error.
- Results: a scrollable `max-h-80` divided list of `FriendRow` items with an action.
- Actions: "Friends" (ghost, disabled), "Request sent" (ghost, disabled), "Accept" (outline), "Add" (outline). While running: "Sending...", "Accepting...".
- Action error under the results.
- No footer. Closing the dialog resets the query and state.

### Remove friend dialog (shadcn AlertDialog)

- Title "Remove {Name}?".
- Text "They will be removed from your friends list. You can send them a new friend request later."
- Buttons "Cancel" and "Remove friend". The action is a normal AlertDialog action (primary), not destructive; it reads "Removing..." while running.
- Closing on success; the page refreshes from the server.

### Avatar form

- 96 px preview that switches to the chosen file via a blob URL.
- "Choose image" outline button wrapping a hidden file input. Accepts `image/jpeg,image/png,image/webp`.
- Field error under the row.
- Rate-limit notice in Error: "Too many photo uploads. Try again in {N}." where N is "N seconds" or "N minutes".
- "Save photo" disabled with no file, while processing, or during the wait.

### Buttons

- Primary: Accent fill, `primary-foreground` text, 40 px high, 6 px radius, weight 500. One primary button per screen or dialog.
- Secondary/outline: no fill, Ink text, 1 px Line border.
- Ghost/text: no fill, no border, Ink or Quiet text.
- Destructive: Error fill for the account-delete trigger.

### Form fields

- Label above in `text-sm font-medium`.
- Input 40 px high, Surface fill, 1 px Line border, 6 px radius.
- Helper text in Quiet under the field where used.
- On error the message appears under the field in Error (`text-sm text-destructive`).
- The profile bio has no live counter.
- The avatar input is visually hidden; "Choose image" is a label.

### Toasts (Sonner)

- Bottom centre, Surface fill, Ink text, 14 px.
- Working: `toast.info("{Name} sent you a message")` in `chat/show` when a message arrives from a conversation other than the open one.
- Working: the `Inertia::flash('toast', …)` messages "Profile updated.", "Photo updated.", and "Password updated." The `useFlashToast` hook is mounted inside the root `Toaster`.
- Not working: friend-action confirmations. `FriendRequestController` uses `back()->with('success', …)`, which is regular session flash and does not reach the Inertia `flash` prop. Do not promise toasts for sent, accepted, declined, cancelled, or removed.
- Flash strings that exist in the backend: "Profile updated.", "Photo updated.", "Friend request sent.", "Friend request accepted.", "Friend request declined.", "Friend request cancelled.", "Friend removed."

### Banners

- None. There is no "Reconnecting…" banner.

## 6. Copy Deck

These strings are in the code. Use them as written.

| Where                              | Text                                                                                         |
| ---------------------------------- | -------------------------------------------------------------------------------------------- |
| Sidebar title                      | Chats                                                                                        |
| Sidebar friends row                | Friends                                                                                      |
| Sidebar chat search                | Search...                                                                                    |
| Sidebar list heading               | Direct Messages                                                                              |
| Sidebar empty                      | No chats yet. Search for someone to start.                                                   |
| Sidebar footer menu                | Settings / Log out                                                                           |
| Mobile chats title                 | Chats                                                                                        |
| Mobile chat search                 | Search chats or people                                                                       |
| Mobile filters                     | All / Unread                                                                                 |
| Mobile active strip                | Active friends / {n} online                                                                  |
| Mobile chat empty                  | No chats yet. Find a friend to start.                                                        |
| Mobile chat no results             | No conversations found.                                                                      |
| Mobile chat CTA                    | Find friends                                                                                 |
| Mobile tab bar                     | Chats / Friends / Profile                                                                    |
| Chat empty pane                    | Select a conversation to start chatting.                                                     |
| Chat back control                  | Back to chats                                                                                |
| Conversation preview (new friend)  | You're now friends with {Name}.                                                              |
| Conversation preview (hidden)      | This message was deleted                                                                     |
| Conversation empty                 | No messages yet. Say hello.                                                                  |
| Composer placeholder               | Write a message...                                                                           |
| Send button                        | Send                                                                                         |
| Message options accessible name    | Message options                                                                              |
| Menu item                          | Delete message                                                                               |
| Delete dialog                      | Delete message / This message will be hidden from the other person. You'll still see it. / This message will be removed from your view. The other person will still see it. / Cancel / Delete |
| Deleted message                    | This message was deleted                                                                     |
| Delete failure                     | Couldn't delete the message. Try again.                                                      |
| Date lines                         | Today / Yesterday / Monday / November 9, 2026                                                |
| Read-only thread                   | You're no longer friends with {Name}. You can still read your conversation, but you can't send new messages. |
| Friends page title                 | Friends                                                                                      |
| Friends primary action             | Find people                                                                                  |
| Friends section titles             | Friend requests / Sent requests / Your friends                                               |
| Friends section counts             | {n} pending / {n} sent / {n} friend / {n} friends                                             |
| Friends row actions                | Accept / Decline / Cancel request / Remove                                                   |
| Friends empty                      | You haven't added any friends yet.                                                           |
| Remove friend dialog               | Remove {Name}? / They will be removed from your friends list. You can send them a new friend request later. / Cancel / Remove friend |
| Find people dialog                 | Find people / Search by username / Add / Request sent / Accept / Friends / Sending... / Accepting... |
| Find people hints                  | Type at least 2 characters to search. / Searching... / No users found for @{term}. / Couldn't search right now. Try again. |
| Profile page                       | Profile / Update your profile information / Name / Username / Bio / Email address / Save      |
| Photo section                      | Photo / Update your profile picture / Choose image / Save photo                              |
| Avatar wait                        | Too many photo uploads. Try again in {N seconds or minutes}.                                 |
| Delete account                     | Delete account / Warning / Please proceed with caution, this cannot be undone.               |
| Appearance                         | Appearance settings / Update the appearance settings for your account / Light / Dark / System |
| Settings navigation                | Profile / Security / Appearance                                                              |
| Toast (info)                       | {Name} sent you a message                                                                    |
| Toast (flash)                      | Profile updated. / Photo updated. / Password updated.                                        |
| Page-level 403/404                 | Inertia's default error response; no custom copy                                             |

## 7. States

| State                 | What the user sees                                                                                       |
| --------------------- | -------------------------------------------------------------------------------------------------------- |
| Loading search        | A spinner inside the search field while the debounced request runs.                                       |
| Empty thread          | "No messages yet. Say hello." centred in the pane.                                                        |
| Empty sidebar         | "No chats yet. Search for someone to start."                                                              |
| Empty mobile list     | "No chats yet. Find a friend to start." plus a "Find friends" button.                                     |
| No friends            | "You haven't added any friends yet."                                                                      |
| No search results     | "No users found for @{term}."                                                                             |
| Search failed         | "Couldn't search right now. Try again."                                                                   |
| Avatar failed         | Field error under the photo row in Error. The current avatar stays.                                       |
| Avatar rate limited   | Error line with the wait time and "Save photo" disabled. The preview stays.                               |
| Friend action running | The clicked action disables and changes label: "Sending...", "Accepting...", "Declining...", "Cancelling...", "Removing...". |
| Friend action failed  | `errors.user` or `errors.friendship` under the search results, or the default Inertia error response.     |
| Delete running        | Dialog action reads "Deleting...".                                                                         |
| Delete failed         | "Couldn't delete the message. Try again." inside the dialog.                                               |
| Message hidden        | "This message was deleted" in italic; the menu is gone.                                                    |
| Thread read-only      | The composer is replaced by the "no longer friends" notice.                                                |
| Incoming message      | Appended to the open thread, or an info toast if it belongs to another conversation.                       |
| Active                | Green dot in the sidebar, header, and Friends rows; teal dot in the mobile list and active strip. Disappears after 5 minutes without a request. |
| Disconnected          | No special state. Existing content stays; live updates pause.                                              |
| 403 / 404             | Inertia's default error response.                                                                          |

## 8. Accessibility Target

These are targets. No automated audit is stored in the repo.

- Every control is keyboard reachable and has a visible focus ring (2 px Accent with offset).
- Dialogs trap focus, close on Escape, and return focus to the trigger.
- Icon-only buttons have names: "Back to chats", "Account menu", "Message options", "Active".
- Field errors are rendered next to their field. Shadcn `InputError` is a plain paragraph, not linked with `aria-describedby`.
- The message list is not a live region. New messages are not announced.
- Colour is never the only signal: errors have text, unread has a number, friend state is written in words, and your messages are also on the right.
- Touch targets are at least 40 px high (`min-h-11` on the tab bar).
- Text pairs meet 4.5:1 in light mode. Dark mode has not been contrast-audited.
- Reduced motion is not audited.

## 9. shadcn/ui Mapping

The tokens are defined as CSS variables in `resources/css/app.css` and mapped to shadcn's theme variables.

| shadcn variable                                                           | Token   |
| ------------------------------------------------------------------------- | ------- |
| `--background`                                                            | Paper   |
| `--foreground`                                                            | Ink     |
| `--card`, `--popover`                                                     | Surface |
| `--card-foreground`, `--popover-foreground`                               | Ink     |
| `--primary`, `--ring`                                                     | Accent  |
| `--primary-foreground`                                                    | `#FFFFFF` in light, `#0F1A19` in dark |
| `--secondary`, `--muted`, `--accent`                                       | Mine    |
| `--secondary-foreground`, `--accent-foreground`                           | Ink     |
| `--muted-foreground`                                                      | Quiet   |
| `--border`, `--input`                                                     | Line    |
| `--destructive`                                                           | Error   |
| `--radius`                                                                | 6 px    |

The 16 px message radius comes from `rounded-2xl`. The dark palette overrides the same variables under `.dark`.

Components used: Button, Input, Textarea, Label, Avatar, Dialog, AlertDialog, DropdownMenu, Sonner, Sidebar, Separator, Tooltip, Spinner, and the starter-kit security/passkey components.

## 10. Choices Made on Purpose

- No cream or warm-clay palette, and no black with a neon accent. The palette is cool white, slate ink, and one muted teal.
- No bubble tails, gradients, shadows, or card grids.
- No avatars or names inside the message list, because there are only two people.
- Your own messages are the only filled shape in the chat. Everything near them stays quiet.
- Friendship is a gate, not a badge. Requests live on one Friends page instead of being mixed into the chat list, so unread messages and relationship state never compete for the same row.
- Accept and Decline are quiet row actions; "Find people" is the only primary button on the page.
- Removing a friend is not blocking. The row leaves the list, but the existing conversation stays readable.
- The mobile chat list is a separate, richer screen from the desktop sidebar: search, filters, active strip, previews, and times. Desktop keeps the sidebar narrow.
- The shared active dot is green instead of teal so it reads as "present" rather than "actionable". The mobile list and active strip keep the teal accent.

## 11. Known Visual Gaps

- Friend-action confirmations do not render: sent, accepted, declined, cancelled, and removed are silent. Profile, photo, and password saves do toast.
- The composer has no error state.
- The active dot is state from the last request, not live presence.
- The sidebar conversation badge caps at "99+" while the mobile list and tab bar cap at "9+".
- There is no "Reconnecting…" banner.
- Dark-mode contrast and reduced motion have not been audited.

## 12. Not Covered Here

The following are not built: rooms, group avatars, attachments, reactions, message editing, message search, friend suggestions, mutual friends, typing indicators, blocking, reporting, stranger message requests, read receipts inside the thread, live friend events, pagination, and a new-chat dialog. See `PRD.md` section 15 for the full list.
