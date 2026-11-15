# GS APACOPE School Platform (Phase 1)
Laravel + MySQL or PostgreSQL. Phase 1: public website, login by role, dashboards, Attendance, Absence follow-up, Marks, Timetable, Announcements, Fees.

## Setup (Laragon, Windows)
1. Install Laragon (Full). In `php.ini` enable `extension=pdo_pgsql` and `extension=pgsql` (or use MySQL, see step 4).
2. Create the base project:  `composer create-project laravel/laravel apacope-app`
3. Copy everything from THIS folder into `apacope-app` (merge, overwrite when asked).
4. Edit `.env`:  `DB_CONNECTION=pgsql  DB_HOST=127.0.0.1  DB_PORT=5432  DB_DATABASE=apacope  DB_USERNAME=postgres  DB_PASSWORD=yourpass`
   (MySQL also works: `DB_CONNECTION=mysql`, port 3306.) Create an empty database named `apacope`.
5. Run:  `php artisan migrate:fresh --seed`  then  `php artisan serve`  and open http://127.0.0.1:8000

## Demo logins (password: `password`)
head@ / dosb@ / dost@ / trainer@ / admin@ / student@ / parent@ / accountant@ / storekeeper@ `apacope.test`
- trainer@ sees ONLY the L5 Software Development class. dosb@ and dost@ follow up absences in their sections. head@ sets the 3/5/8 rules.

## Next phases
Marks, timetable, learning materials, announcements, parent portal, fees, inventory, calendar, reports, IT admin console, React (Inertia) front end.

## Update notes (v3)
- Run once: `php artisan storage:link` (needed for uploaded files).
- Delete old files if present: `resources/views/welcome.blade.php`, `public/js/intro.js`.
- Then: `php artisan migrate:fresh --seed`
- The "Viewing as" menu in the top bar works only while `APP_DEBUG=true` in `.env`. Set `APP_DEBUG=false` before going live.

## Update notes (v4)
- Run `php artisan migrate:fresh --seed` (new tables: modules, assessments, scores, audit_logs, assets).
- Attendance: a date with saved attendance is locked. Only the DOS can reopen it.
- Marks: per module, each test (formative, summative, quiz...) has its own max and is locked after saving. Only the DOS (TVET) can reopen.

## Update notes (v5)
- Run `php artisan migrate:fresh --seed`. Role switching ("Viewing as") has been removed: log out and sign in as another account. Demo account buttons on the login page appear only while `APP_DEBUG=true`.
- Parents register at /register: verified against the student list (name and class, max two parents per student) and approved instantly. Students need DOS approval (Account requests).
- Timetable can only be edited by the DOS. Marks can be corrected by the trainer. Fees: the accountant records payments, reasons and promise dates.

## Update notes (v6)
- Run `php artisan migrate:fresh --seed` (new table: periods).
- DOS sets the start and end time of every period (up to 8) in Timetable. "Who does what" was removed.
- Light/dark toggle (button next to the date, or top right on public pages). The logo splash plays on every opening of the website, login, register and dashboard.

## Update notes (v7)
- New table for the welcome page content. Run `php artisan migrate` (keeps your data), then `php artisan db:seed --class=SiteSeeder` for sample notices and events. Or run `php artisan migrate:fresh --seed` to reset everything.
- The IT admin manages the welcome page notices, upcoming events and contact details in "Website content".

## Update notes (v8)
- Run `php artisan migrate` (new tables). Then optionally `php artisan db:seed --class=SiteSeeder`.
- New: parent notifications (absent today, follow-ups, low marks), reading acknowledgments, per-student attendance rates by period, receipts, expenditure, documents (minutes, letters, forms, reports), password reset requests, class and trainer setup, session plans, observations and CPD, materials with descriptions, outcomes, scheduling and favorites. The print button (top bar) saves any page as PDF.

## Update notes (v9)
- Run `php artisan migrate` (new table: announcement_targets).
- The GSA logo opening plays only right after logging in.
- Announcements: the sender chooses recipients (roles, individual teachers, students or parents of classes). Recipients tick "I have read this"; the sender sees who has read it.

## Update notes (v10)
- Run `php artisan migrate`, then `php artisan db:seed --class=Phase10Seeder` for sample outcomes, attachments, media and an assignment.
- Backups: the IT admin has a Backups page. For the nightly automatic backup, the Laravel scheduler must run. On Windows, create a Task Scheduler task that runs `php artisan schedule:run` every minute in the project folder. Restore: `php artisan apacope:restore FILE.json.gz`.
- Videos up to 100 MB need `upload_max_filesize` and `post_max_size` of at least 100M in php.ini.
- Permissions: the IT admin switches features on or off per role in Permissions.

## Update notes (v11)
- Uploads up to 100 MB: start the site with **start.bat** (Windows) instead of `php artisan serve`. It starts the server with `upload_max_filesize=100M` and `post_max_size=105M`. With Laragon's own Apache, open Menu > PHP > php.ini, set `upload_max_filesize = 100M` and `post_max_size = 105M`, then restart Laragon. `public/.user.ini` also carries these values for FastCGI hosting.
- Run `php artisan apacope:check` to see if everything needed is set up.
- Email and SMS delivery was removed: parent messages are shown in the parent portal only.
- Backups: for the nightly automatic backup, create a Windows Task Scheduler task that runs `php artisan schedule:run` every minute in the project folder.

## Update notes (v12)
- Run `php artisan migrate` (assignments now create a test column in Marks; media is shared with chosen classes; portfolio categories).
- The logo splash plays when the website opens and after login.
- Staff attendance: once a staff member is marked, it is saved and locked. The Head Teacher marks everyone; a DOS marks the trainers of his classes. Only the Head Teacher can reopen a mark.

## Update notes (v13)
- Run `php artisan migrate`, then `php artisan view:clear`. Start with `start.bat`.
- New colours: soft blue, warm coral and light surfaces (light and dark toggle kept).
- Test everything: `php artisan apacope:selftest` opens every page for every demo role. `docs/PDF_CHECKLIST.md` lists every requirement from the PDF with where to check it.
- New uploads are stored privately and are opened through a permission check.
