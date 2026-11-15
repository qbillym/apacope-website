# GS APACOPE: checklist against the requirements PDF

Status words: **Built** = code written and checked for syntax and links; **Partly** = some of it; **Not built**. Nothing here was run by the person who wrote it, so use this list to test. Demo password for every account: `password` (emails like trainer@apacope.test).

Quick test: run `php artisan apacope:selftest` first. It opens every page for every role and prints OK or FAIL.

**Totals: 81 built, 4 partly, 1 not built, out of 86 checks.**

| # | Section | Requirement | Status | Sign in as | Where and how to check |
|---|---|---|---|---|---|
| 1 | Introduction | One platform for Head Teacher, DOS, trainers, students, parents, IT admins | Built | Any demo account | Sign in as each role: every role has its own menu and its own Your tasks list |
| 2 | Introduction | Dashboard shows the most important information right after login | Built | Any | /home, Your tasks card first |
| 3 | 1 Dashboard | Total students by class, level and trade | Built | head@ or dosb@ | Dashboard, Enrollment by class, level and trade |
| 4 | 1 Dashboard | Attendance: present, absent, late, excused | Built | head@ | Dashboard, Attendance card |
| 5 | 1 Dashboard | Class averages and failed modules | Built | head@ | Dashboard, Academic performance |
| 6 | 1 Dashboard | Learning progress | Built | head@ | Dashboard, Learning outcomes covered bar |
| 7 | 1 Dashboard | Urgent alerts: frequent absences, missing marks, pending tasks | Built | dosb@ | Dashboard, Urgent alerts and Pending tasks |
| 8 | 2 Attendance | Trainer takes attendance by class and date | Built | trainer@ | Attendance, choose date, Save register |
| 9 | 2 Attendance | Present, Absent, Late, Excused | Built | trainer@ | Attendance buttons |
| 10 | 2 Attendance | Saved register is locked; only the DOS reopens it | Built | trainer@ then dost@ | Save, try to edit, then DOS presses Reopen for correction |
| 11 | 2 Attendance | History by day, week, term, year with percentages | Built | trainer@ | Attendance, Attendance percentage per student, Previous registers |
| 12 | 2 Attendance | Frequently absent students found automatically | Built | dost@ | Absence follow-up |
| 13 | 2 Attendance | Rules (3 / 5 / 8) set by management | Built | head@ | Absence follow-up, Rules |
| 14 | 2 Attendance | Follow-up register: who, when, what agreed | Built | dost@ | Save a follow-up, Follow-up register |
| 15 | 2 Attendance | Excused absences kept apart from unexplained | Built | dost@ | Absence follow-up columns |
| 16 | 2 Attendance | Followed-up student leaves the list and returns if absent again | Built | dost@ and trainer@ | Save a follow-up, take a new register with him absent, he returns in red |
| 17 | 2 Attendance | Reports for DOS, Head Teacher, staff | Built | head@ | Reports and exports |
| 18 | 2 Attendance | Parent is told about absences | Built | trainer@ then parent@ | Save a register with an absent student, parent dashboard Messages from the school |
| 19 | 3 Materials | Post a video link or upload a file or video (to 100 MB) | Built | trainer@ | Learning materials, Publish (start with start.bat for 100 MB) |
| 20 | 3 Materials | Class, module, learning outcome, title, description | Built | trainer@ | Publish form |
| 21 | 3 Materials | Publish now or schedule; replace or remove | Built | trainer@ | Publish on date; Edit or replace; Remove |
| 22 | 3 Materials | See which students viewed it | Built | trainer@ and student@ | Student opens a material, trainer sees Viewed by N of M students |
| 23 | 3 Materials | Search, module filter, favorites | Built | student@ | Library filters and the Save star |
| 24 | 3 Materials | Comments and questions | Built | student@ and trainer@ | Comments and questions panel |
| 25 | 3 Materials | Class notified when material is posted | Built | student@ | Announcements |
| 26 | 3 Materials | Low-data mode | Built | Any | Press the signal button at the top |
| 27 | 3 Media | Media and Engagement section, six categories | Built | Any | Media and Engagement |
| 28 | 3 Media | Moderated; shown only to chosen classes | Built | student@, dost@, head@ | Student shares; DOS approves; only that class sees it; Head sees all |
| 29 | 4 Academic | Student registration and profiles (admission no, class, level, trade, guardians, previous record, status) | Built | dosb@ | Students, open a profile |
| 30 | 4 Academic | Marks: formative, summative, comprehensive, totals, grades, competency | Built | trainer@ | Marks and assessment |
| 31 | 4 Academic | Students who need support | Built | trainer@ | Overall results, Needs support |
| 32 | 4 Academic | Assignments with marks added to overall results | Built | trainer@ and student@ | Create assignment, enter marks, student sees Marks x / y |
| 33 | 4 Academic | Timetable: lessons, rooms, trainers, practical, DOS-set period times | Built | dost@ | Timetable |
| 34 | 4 Academic | Timetable change notifies the class | Built | student@ | Announcements after the DOS saves |
| 35 | 4 Academic | Examinations inside the timetable | Partly | dost@ | Exams are in the Calendar, not in the weekly grid |
| 36 | 4 Academic | Report card | Built | student@ or parent@ | Marks, Report card, Print |
| 37 | 4 TSS | Levels, trades, modules, learning outcomes | Built | dost@ | TSS records; Admin can set trade and level when adding a class |
| 38 | 4 TSS | Practical assessments and competency results | Built | dost@ | TSS records, create a Practical test in Marks first |
| 39 | 4 TSS | Industrial attachment records | Built | dost@ | TSS records, Industrial attachment |
| 40 | 5 Trainer and DOS | Trainer profiles, modules, teaching load | Built | dosb@ or head@ | Staff supervision |
| 41 | 5 Trainer and DOS | Session plans and delivery records | Built | trainer@ then dost@ | Session plans; Staff supervision |
| 42 | 5 Trainer and DOS | Attendance register for staff, locked once saved | Built | head@ or dosb@ | Staff attendance |
| 43 | 5 Trainer and DOS | Assessment schedules and mark-entry deadlines | Built | dost@ | Staff supervision, Tests waiting for marks |
| 44 | 5 Trainer and DOS | Learning outcomes covered | Built | dost@ | TSS records, progress bars |
| 45 | 5 Trainer and DOS | Trainer and trainee portfolios | Built | trainer@, student@ | Portfolio; Staff supervision flags Needs updating |
| 46 | 5 Trainer and DOS | Classroom observation and feedback | Built | dost@ | Staff supervision |
| 47 | 5 Trainer and DOS | Overdue tasks and incomplete work | Built | dost@ | Your tasks and Staff supervision |
| 48 | 5 Trainer and DOS | CPD sessions | Built | dost@ | Staff supervision, CPD sessions |
| 49 | 5 Trainer and DOS | Trainer performance and delivery report | Built | dost@ | Staff supervision tables, print as PDF |
| 50 | 6 Communication | Announcements by Head Teacher and DOS to chosen people | Built | head@, dosb@ | Announcements, tick recipients |
| 51 | 6 Communication | Class messages by trainers (own class only) | Built | trainer@ | Announcements |
| 52 | 6 Communication | Notices about meetings, events, closures | Built | Any | Calendar and events |
| 53 | 6 Communication | Exam timetables and deadline reminders | Partly | student@ | Calendar and Your tasks; no automatic reminder messages |
| 54 | 6 Communication | Parent notifications for attendance and academic concerns | Built | parent@ | Messages from the school |
| 55 | 6 Communication | Acknowledgment of who has read a notice | Built | student@ and sender | I have read this; sender sees Read by N of M |
| 56 | 6 Communication | Optional SMS or email delivery | Not built | - | Removed: needs your own mail server or SMS account |
| 57 | 6 Communication | Only authorised staff publish school-wide notices | Built | trainer@ | Trainer cannot choose the whole school |
| 58 | 7 Parent portal | Secure account linked to own child, checked against the student list | Built | Register page | Register as parent with child's name and class |
| 59 | 7 Parent portal | Daily attendance and repeated absences | Built | parent@ | Dashboard, Recent attendance |
| 60 | 7 Parent portal | Progress and report card | Built | parent@ | Marks; Report card |
| 61 | 7 Parent portal | Announcements and exam dates | Built | parent@ | Announcements; Calendar |
| 62 | 7 Parent portal | Homework and assignments | Built | parent@ | Assignments |
| 63 | 7 Parent portal | Messages from the school | Built | parent@ | Dashboard |
| 64 | 7 Parent portal | Fee balance and receipts | Built | parent@ | Dashboard, Payments and receipts |
| 65 | 7 Parent portal | Sees only own child | Built | parent@ | No other student appears anywhere |
| 66 | 8 Other | Fee accounts, invoices, payments, balances | Built | accountant@ | Fees and parents |
| 67 | 8 Other | Receipts | Built | accountant@ | Student page, Receipt |
| 68 | 8 Other | Expenditure | Built | accountant@ | Expenditure |
| 69 | 8 Other | Financial reports | Partly | accountant@ | Fee balances CSV and Expenditure page |
| 70 | 8 Other | Inventory: borrow, return, damage, maintenance, history | Built | storekeeper@ | Inventory and assets, History |
| 71 | 8 Other | School calendar | Built | head@ | Calendar and events |
| 72 | 8 Other | Documents: minutes, letters, staff forms, management reports | Built | head@ | Documents |
| 73 | 8 Other | PDF or Excel output | Built | Any | Export (opens in Excel) and Print or Save as PDF |
| 74 | 9 Roles | IT admin: accounts, permissions, backups, settings | Built | admin@ | IT administration, Permissions, Backups, Website content |
| 75 | 9 Roles | Head Teacher oversight and staff supervision | Built | head@ | Dashboard, Staff supervision, Reports |
| 76 | 9 Roles | DOS, trainer, student, parent, accountant, storekeeper tools | Built | each demo account | Each has its own menu |
| 77 | 10 Security | Secure login, role-based access | Built | Any | Sign in; try another role's URL, you get access denied |
| 78 | 10 Security | Password reset | Partly | admin@ | IT admin gives a temporary password; no email self-service |
| 79 | 10 Security | Protection against common web attacks | Built | - | CSRF tokens, validation, login throttling, hashed passwords |
| 80 | 10 Security | Automatic backups and tested recovery | Built | admin@ | Backups, Check recovery (needs the Windows scheduler task) |
| 81 | 10 Security | Audit logs for marks, attendance, records | Built | admin@ | IT administration, Audit log |
| 82 | 10 Security | Secure uploads with type and size limits | Built | trainer@ | Try a wrong file type or a file over 100 MB |
| 83 | 10 Security | Privacy of student files | Built | student@ | New uploads are private and open only for allowed people |
| 84 | 10 Security | Responsive on phones, tablets, computers | Built | Any | Open on a phone; not tested on real devices |
| 85 | 10 Security | Works on limited internet | Built | Any | Low-data mode, light pages |
| 86 | 10 Security | Academic year, term, class, trade, module setup | Built | admin@ | IT administration, term, add class with trade and level |
