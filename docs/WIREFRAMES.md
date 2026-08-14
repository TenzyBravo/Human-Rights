# Wireframes

Low-fidelity layouts for every major screen, with the responsive behaviour noted. All are
implemented in `app/Views/`; these are the design intent behind them.

Breakpoints follow Bootstrap: `sm` 576, `md` 768, `lg` 992, `xl` 1200.

---

## 1. Public homepage — `app/Views/public/home.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [◆ HRRAMS]   Campaigns Resources News Events Statistics Track  [Report ▸] [🌙]│  sticky, glass
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ◦ CONFIDENTIAL & SECURE                    ┌────────────────────────────┐   │
│                                             │ PLATFORM AT A GLANCE       │   │
│  Report a human rights violation.           │ ┌────────┐ ┌────────┐      │   │
│  Have it taken seriously.                   │ │  1,284 │ │   946  │      │   │
│                                             │ │ reports│ │concluded      │   │
│  File a complaint online in minutes —       │ └────────┘ └────────┘      │   │
│  with your name or anonymously. Every       │ ┌────────┐ ┌────────┐      │   │
│  report gets a reference, an officer        │ │     12 │ │     10 │      │   │
│  and a tracked investigation.               │ │campaign│ │province│      │   │
│                                             │ └────────┘ └────────┘      │   │
│  [ Report a violation ] [ Track a report ]  │ View full statistics →     │   │
│  ☎ Emergency hotline 933                    └────────────────────────────┘   │
├──────────────────────────────────────────────────────────────────────────────┤
│                       WHAT CAN YOU REPORT?                                   │
│  [🔒 Arbitrary Arrest] [⚠ Torture] [⚥ GBV] [👥 Child Rights]                 │
│  [⊘ Discrimination]    [📣 Expression] [💼 Labour] [📖 Education]  … 12 total │
├──────────────────────────────────────────────────────────────────────────────┤
│  FROM REPORT TO RESOLUTION                                                   │
│  ① Submit   ② Review   ③ Investigate   ④ Resolve                             │
├──────────────────────────────────────────────────────────────────────────────┤
│  CURRENT CAMPAIGNS                                          [ View all ]     │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐                       │
│  │ Campaign      │ │ Campaign      │ │ Campaign      │   lift on hover       │
│  └───────────────┘ └───────────────┘ └───────────────┘                       │
├──────────────────────────────────────────────────────────────────────────────┤
│  EDUCATIONAL RESOURCES (7/12)        │  LATEST NEWS + UPCOMING EVENTS (5/12) │
├──────────────────────────────────────────────────────────────────────────────┤
│  FOOTER — brand · hotline · Report · Learn · About · Account                  │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Responsive.** Hero collapses to one column below `lg`, statistics card moving below the
copy. Category grid steps 4 → 3 → 2 → 1. Navbar collapses to a hamburger below `lg`, with the
"Report a violation" button staying visible.

---

## 2. Sign in — `app/Views/auth/login.php` (layout `auth`)

```
┌───────────────────────────────────┬──────────────────────────────────────────┐
│                                   │  ← Back to site                     [🌙] │
│  [◆ HRRAMS]                       │                                          │
│                                   │  Welcome back                            │
│  Every right deserves             │  Sign in to manage reports and cases.    │
│  a voice.                         │                                          │
│                                   │  Email address *                         │
│  A secure platform for            │  ┌─────────────────────────────────────┐ │
│  reporting violations,            │  │ ✉  you@example.org                  │ │
│  tracking investigations          │  └─────────────────────────────────────┘ │
│  and building awareness.          │                                          │
│                                   │  Password *        Forgot password?      │
│  🔒 Encrypted submissions         │  ┌──────────────────────────────┬──────┐ │
│  👁 Anonymous supported           │  │ 🔒 ••••••••••                │  👁  │ │
│  🕘 Transparent tracking          │  └──────────────────────────────┴──────┘ │
│  👥 Awareness programmes          │                                          │
│                                   │  [Security question — after 2 failures]  │
│  [ Report without an account ]    │  ☐ Keep me signed in                     │
│                                   │  [        Sign in        ]               │
│  gradient: blue → teal            │  No account yet? Create one              │
│                                   │  ─────────────────────────────────────   │
│                                   │  👁 You do not need an account to report │
└───────────────────────────────────┴──────────────────────────────────────────┘
```

**Responsive.** The branding panel is hidden below `lg`; the form centres with a 27 rem cap.

---

## 3. Reporting wizard — `app/Views/report/create.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    ◦ CONFIDENTIAL SUBMISSION                                 │
│              Report a human rights violation                                 │
├──────────────────────────────────────────────────────────────────────────────┤
│ ━━━━━━━━━━━━━━━  ──────────────  ──────────────  ──────────────              │
│ 1. Who is        2. The          3. Evidence     4. Review &                 │
│    reporting        incident                        submit                   │
├──────────────────────────────────────────────────────────────────────────────┤
│  STEP 1                                                                      │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ (●──) Report anonymously                                               │  │
│  │ With this on, no name, phone, email or address is stored. You still    │  │
│  │ get a reference number — write it down, we cannot recover it for you.  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  Full name *              │ Phone number          ← hidden when anonymous    │
│  Email address            │ Nationality                                      │
│  Address                                                                     │
├──────────────────────────────────────────────────────────────────────────────┤
│  STEP 2                                                                      │
│  Short title * ······················· │ Urgency * [Medium ▾]                │
│  Category of violation * [ ▾ ]                                               │
│  What happened? *  ┌──────────────────────────────────────────────────────┐  │
│                    │ (7 rows, min 30 chars)                               │  │
│                    └──────────────────────────────────────────────────────┘  │
│  Date [    ] │ Time [    ] │ Victims [ 1 ]                                   │
│  Province [ ▾ ] → District [ ▾ ]   ← AJAX cascade                            │
│  Specific location ···········································                │
│  ┌── GPS coordinates (optional) ──────── [ Use my current location ] ─────┐  │
│  │ Latitude ········  Longitude ········                                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  Persons involved │ Organisation involved │ Witness details                  │
├──────────────────────────────────────────────────────────────────────────────┤
│  STEP 3                                                                      │
│  ┌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┐  │
│  ╎                        ☁ upload                                       ╎  │
│  ╎          Drop files here, or click to browse                          ╎  │
│  ╎   JPG PNG WEBP GIF MP4 MOV MP3 WAV PDF DOC DOCX · 20 MB each          ╎  │
│  └╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌╌┘  │
│  (📎 clinic-report.pdf 1.2 MB) (📎 photo.jpg 0.8 MB)   Clear               │
│  ⚠ Never put yourself at risk to gather evidence.                           │
├──────────────────────────────────────────────────────────────────────────────┤
│  STEP 4 — REVIEW                                                             │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ Reported by      Category        Urgency      Incident date            │  │
│  │ Joseph Tembo     Labour Rights   High         2026-07-14               │  │
│  │ Province         District        Location     Evidence files          │  │
│  │ Southern         Choma           Choma市場     2 attached             │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  ☐ Consent declaration * — I confirm this information is true …             │
│  ℹ You will receive a reference such as HR-2026-000123.                     │
├──────────────────────────────────────────────────────────────────────────────┤
│  [ ← Back ]                              Cancel  [ Continue → ] / [ Submit ] │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Behaviour.** Steps are validated one at a time with the HTML5 constraint API before advancing,
so a user is never told about an error on a step they cannot see. The anonymous switch hides
the identity block *and* removes its `required` attributes, then clears the values. The review
panel is rebuilt from live field values on entry to step 4. The whole form is one POST — no
partial server-side state to lose.

---

## 4. Officer dashboard — `app/Views/dashboard/index.php` (layout `app`)

```
┌───────────────┬──────────────────────────────────────────────────────────────┐
│ [◆ HRRAMS]    │ ⌂ / Dashboard        [🔍 search] [🔔³] [🌙] [DP Daniel P. ▾] │
│               ├──────────────────────────────────────────────────────────────┤
│ OVERVIEW      │ Good morning, Daniel                                         │
│ ▸ Dashboard   │ Monday, 13 August 2026 · Officer dashboard                   │
│               │                                                              │
│ CASE WORK     │ ┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐                     │
│ ▸ My cases    │ │  14  ││   3  ││   1  ││  38  ││ 21 d │  clickable tiles    │
│ ▸ Overdue     │ │active││overdu││escal.││concl.││avg   │                     │
│               │ └──────┘└──────┘└──────┘└──────┘└──────┘                     │
│ MY ACCOUNT    │ ┌─────────────────────────────────┬────────────────────────┐ │
│ ▸ My reports  │ │ REPORT VOLUME, LAST 12 MONTHS   │ ⚠ NEEDS ATTENTION NOW  │ │
│ ▸ My profile  │ │      ╱╲    ╱╲                   │ • Detention at post    │ │
│               │ │  ╱╲╱  ╲╱╲╱  ╲╱╲                 │   CASE-2026-00031 41 d │ │
│ [+ New report]│ │ ╱                ╲              │ • Unpaid wages         │ │
│               │ └─────────────────────────────────┤   CASE-2026-00028 36 d │ │
│               │ ┌───────────────┬───────────────┐ ├────────────────────────┤ │
│               │ │ BY CATEGORY   │  BY STATUS    │ │ 📅 UPCOMING INTERVIEWS │ │
│               │ │ ▄▄▄▄▄▄▄▄      │     ◕         │ │ [14 AUG] Complainant   │ │
│               │ │ ▄▄▄▄▄         │  doughnut     │ │ [16 AUG] Site witness  │ │
│               │ └───────────────┴───────────────┘ ├────────────────────────┤ │
│               │ ┌─────────────────────────────────┤ 🔔 NOTIFICATIONS       │ │
│               │ │ MY OPEN CASES                   │ • New case assigned    │ │
│               │ │ Case │ Subject │ Pri │ Sts │Age │ ├────────────────────────┤ │
│               │ │ …8 rows, row click → case…      │ │ LATEST REPORTS         │ │
│               │ └─────────────────────────────────┴────────────────────────┘ │
└───────────────┴──────────────────────────────────────────────────────────────┘
```

**Responsive.** The sidebar becomes an off-canvas drawer below `lg` (hamburger + backdrop, Esc
to close). KPI tiles wrap 5 → 2 per row. The right-hand column stacks under the charts below
`xl`. Charts load over AJAX with shimmer skeletons.

---

## 5. Case detail — `app/Views/cases/show.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ← Back to cases                                                              │
│ Unlawful detention at Kanyama police post          [High priority] [ASSIGNED] │
│ Case CASE-2026-00031 · Report HR-2026-000117 · Opened 03 Jul 2026            │
├──────────────────────────────────────────────────────────────────────────────┤
│ 🔔 This case passed its service deadline on 10 Jul 2026. Record progress or   │
│    escalate it.                                                    (danger)  │
├─────────────────────────────────────────────┬────────────────────────────────┤
│ THE COMPLAINT                               │ ASSIGNMENT                     │
│ (full description, preserved line breaks)   │ (DP) Daniel Phiri              │
│ ─────────────────────────────────────────── │      officer@hrrams.test       │
│ Category      │ Incident date & time        │ Supervisor  Grace Mulenga      │
│ Arbitrary…    │ 02 Jul 2026 · 21:30         │ Due by      10 Jul 2026        │
│ Location      │ GPS coordinates             │ ────────────────────────────── │
│ Kanyama       │ -15.4310, 28.2650 → map     │ Reassign to [ ▾ ] (3 open)     │
│ Victims 2     │ Organisation  ZP Kanyama    │ Instruction ················   │
│ Persons involved / Witness details          │ [   Reassign case   ]          │
├─────────────────────────────────────────────┼────────────────────────────────┤
│ RECORD AN ACTION                            │ COMPLAINANT                    │
│ [Case note][Findings][Interview][Info][Sts] │ Name    Joseph Tembo           │
│ ┌─────────────────────────────────────────┐ │ Email   joseph@example.org     │
│ │ Heading ·······························  │ │ Phone   +260 97 000 0005       │
│ │ Note * ┌─────────────────────────────┐  │ │ 🔒 Every view is audit-logged. │
│ │        │                             │  │ ├────────────────────────────────┤
│ │        └─────────────────────────────┘  │ │ EVIDENCE                    ③  │
│ │ ☐ Visible to the complainant  [+ Add]   │ │ 📄 clinic-report.pdf  1.2 MB   │
│ └─────────────────────────────────────────┘ │ 🖼 photo.jpg          0.8 MB   │
├─────────────────────────────────────────────┤ 🎵 statement.mp3      3.1 MB   │
│ CONCLUDE THE CASE                       [⌄] │ ────────────────────────────── │
│ (collapsed — supervisors only)               │ RESOLUTION (when concluded)    │
├─────────────────────────────────────────────┴────────────────────────────────┤
│ INVESTIGATION TIMELINE                                          6 entries    │
│ ●─ Status changed to Investigating         Daniel Phiri · 05 Jul 10:12       │
│ │   [Assigned] → [Investigating]                                            │
│ ●─ Interview scheduled            [Shared] Daniel Phiri · 04 Jul 16:40       │
│ │   📅 Scheduled for 14 Aug 2026, 09:00                                      │
│ ●─ Case assigned                           Grace Mulenga · 03 Jul 09:05      │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Behaviour.** Action tabs are filtered by permission — an officer sees notes, findings,
interviews, info requests and investigative status changes; only a `case.close` holder sees
resolve/close/reject options and the conclusion panel. Timeline dot colours encode entry type.

---

## 6. Case list — `app/Views/cases/index.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Case management                                            [ Analytics ]     │
│ ┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐   ← each tile is a filter link      │
│ │  62  ││   7  ││   2  ││ 214  ││   5  │                                     │
│ │active││overdu││escal.││concl.││unass.│                                     │
│ └──────┘└──────┘└──────┘└──────┘└──────┘                                     │
├──────────────────────────────────────────────────────────────────────────────┤
│ Search ·············· │ Status[All open▾] │ Priority[▾] │ Category[▾] │ Prov[▾] │ [⌕][✕] │
├──────────────────────────────────────────────────────────────────────────────┤
│ CASE           │ SUBJECT          │ CATEG │ PRI │ STATUS │ OFFICER │ AGE │    │
│ CASE-2026-00031│ Unlawful detent… │ Arbit │ High│ ASSIGN │ (DP) D…│ 41d🔔│Open│
│ HR-2026-000117 │ 📍 Lusaka        │       │     │        │         │     │    │
│ CASE-2026-00029│ Denied clinic a… │ Health│ Med │ INVEST │ (NB) N…│ 12d │Open│
│ …                                                                            │
├──────────────────────────────────────────────────────────────────────────────┤
│ Showing 1–15 of 62                             [‹] 1 [2] 3 4 5 … 5 [›]       │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Responsive.** The table scrolls horizontally inside its own container below `md`; the page
body never scrolls sideways. Selects auto-submit on change; the search box needs the button.

---

## 7. Track a report (public) — `app/Views/report/track.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                        ◦ CASE TRACKING                                       │
│                      Track your report                                       │
│  ┌────────────────────────────────────────────────────┬──────────────────┐   │
│  │ HR-2026-000123                                     │    [ 🔍 Track ]  │   │
│  └────────────────────────────────────────────────────┴──────────────────┘   │
├──────────────────────────────────────────────────────────────────────────────┤
│  Reference                                              ┌───────────────┐    │
│  HR-2026-000117                                         │ INVESTIGATING │    │
│                                                         └───────────────┘    │
│  ━━━━━━━━━  ━━━━━━━━━  ━━━━━━━━━  ━━━━━━━━━  ─────────  ─────────           │
│  Submitted  Under      Assigned   Investig.   Resolved   Closed              │
│             Review     ✓ done     ← current                                  │
│                                                                              │
│  Subject     Unlawful detention at Kanyama police post                        │
│  Category    Arbitrary Arrest & Detention                                    │
│  Submitted   03 Jul 2026, 08:41                                               │
│  Case file   CASE-2026-00031                                                  │
│                                                                              │
│  [ 📄 Acknowledgement receipt ]  [ 💬 Ask about this case ]                   │
│  For your protection, tracking shows the status only. Case notes, evidence    │
│  and officer details are never exposed publicly.                             │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Admin dashboard — `app/Views/admin/dashboard.php`

```
┌───────────────┬──────────────────────────────────────────────────────────────┐
│ ADMINISTRATION│ Administrator dashboard      [Audit log][Backup][Reports]    │
│ ▸ Admin dash  │ ┌────┐┌────┐┌────┐┌────┐┌────┐┌────┐                         │
│ ▸ Users       │ │1284││ 62 ││  7 ││ 39 ││ 418││  6 │                         │
│ ▸ Roles       │ │rept││case││ovdu││crit││user││offr│                         │
│ ▸ Categories  │ └────┘└────┘└────┘└────┘└────┘└────┘                         │
│ ▸ Settings    │ ┌───────────────────────────────┬──────────────────────────┐ │
│ ▸ Audit logs  │ │ REPORT VOLUME TREND           │ CASE STATUS MIX  ◕       │ │
│ ▸ Backup      │ └───────────────────────────────┼──────────────────────────┤ │
│               │ ┌──────────────┬──────────────┐ │ USER ACCOUNTS            │ │
│               │ │ BY PROVINCE  │ BY CATEGORY  │ │ Active            391    │ │
│               │ │  ▄ ▄█ ▄ █▄   │ ▄▄▄▄▄▄▄▄     │ │ Pending            18    │ │
│               │ └──────────────┴──────────────┘ │ Suspended           9    │ │
│               │ ┌───────────────────────────────┤ [   Manage users   ]     │ │
│               │ │ DENSITY BY PROVINCE/CATEGORY  ├──────────────────────────┤ │
│               │ │        LSK CPB SOU EAS …      │ AWARENESS CONTENT        │ │
│               │ │ Arrest  ██   ▓   ░   ▒        ├──────────────────────────┤ │
│               │ │ GBV     ▓   ██   ▒   ░        │ RECENT ACTIVITY          │ │
│               │ │ Labour  ░    ▒  ██   ▓        │ case.assigned  G.M 2m    │ │
│               │ └───────────────────────────────┴──────────────────────────┘ │
│               │ OFFICER WORKLOAD (table)                                     │
└───────────────┴──────────────────────────────────────────────────────────────┘
```

The heat map is rendered as a real `<table>` with `scope` attributes and per-cell `title`
text, so the density information is available to a screen reader rather than being
colour-only.

---

## 9. Roles & permissions — `app/Views/admin/roles.php`

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ Roles & permissions                                                          │
│ ┌────────────────────────────────┐ ┌────────────────────────────────┐         │
│ │ Human Rights Officer  [ Save ] │ │ Supervisor            [ Save ] │         │
│ │ officer                        │ │ supervisor                     │         │
│ ├────────────────────────────────┤ ├────────────────────────────────┤         │
│ │ REPORTS                        │ │ CASES                          │         │
│ │ ☑ View any report              │ │ ☑ Assign / reassign cases      │         │
│ │   report.view_any              │ │ ☑ Approve investigations       │         │
│ │ ☐ Delete report                │ │ ☑ Close cases                  │         │
│ │ CASES                          │ │ ADMIN                          │         │
│ │ ☑ View assigned cases          │ │ ☐ Manage users                 │         │
│ │ ☑ Update investigation         │ │ ☐ Manage settings              │         │
│ │ … scrollable                   │ │ … scrollable                   │         │
│ └────────────────────────────────┘ └────────────────────────────────┘         │
│ ┌────────────────────────────────┐                                            │
│ │ System Administrator [Full access] ← checkboxes disabled                    │
│ └────────────────────────────────┘                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 10. Design system reference

| Token | Value | Applied to |
|---|---|---|
| Primary | `#0D6EFD` | Primary actions, links, active nav, chart series 1 |
| Secondary | `#20C997` | Success accents, brand gradient, chart series 2 |
| Success | `#198754` | Concluded states, strong password meter |
| Warning | `#FFC107` | Awaiting information, high urgency, drafts |
| Danger | `#DC3545` | Escalated, overdue, critical, destructive actions |
| Dark / Light | `#212529` / `#F8F9FA` | Text and surfaces |
| Radius | 14 px cards, 10 px controls | Cards, inputs, buttons |
| Shadow | `0 1px 2px / 0 8px 24px` rgba | Cards; deeper on hover |
| Headings | Poppins 500–700, `-0.01em` | h1–h6, display, stat values |
| Body | Inter 400–600 | Everything else |
| Icons | Bootstrap Icons 1.11 | Navigation, tiles, timeline, file types |

**Dark mode.** Every colour is a CSS custom property redefined under
`[data-bs-theme="dark"]`, so a single attribute on `<html>` switches the whole interface.
The choice is stored in `localStorage`, defaults to the OS preference, and charts repaint
their axis and legend colours in response to a `hrrams:themechange` event.

**Accessibility.** Skip link on every layout, a visible 3 px focus ring, labels on every
control (`visually-hidden` where the design omits a visual label), `aria-label` on icon-only
buttons, `scope` on table headers, `role="img"` plus `aria-label` on charts with a
table equivalent available, and `prefers-reduced-motion` honoured by disabling animation.
