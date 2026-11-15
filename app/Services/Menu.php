<?php

namespace App\Services;

class Menu
{
    public static function items(): array
    {
        return [
            ['Overview', 'Dashboard', '/home', '◧', null],
            ['Academic', 'Attendance', '/attendance', '✓', ['trainer', 'dosb', 'dost']],
            ['Academic', 'Absence follow-up', '/followup', '⚑', ['dosb', 'dost', 'head']],
            ['Academic', 'Students', '/students', '☺', ['dosb', 'dost']],
            ['Academic', 'Teachers and subjects', '/teachers', '♙', ['dosb', 'dost']],
            ['Academic', 'Account requests', '/requests', '✔', ['dosb', 'dost']],
            ['Academic', 'Marks & assessment', '/marks', '✎', ['trainer', 'student', 'parent', 'dosb', 'dost']],
            ['Academic', 'Assignments', '/assignments', '☑', ['trainer', 'student', 'parent']],
            ['Academic', 'Timetable', '/timetable', '▦', ['trainer', 'dosb', 'dost', 'head', 'student', 'parent']],
            ['Academic', 'TSS records', '/tss', '⚙', ['trainer', 'dost']],
            ['Academic', 'Portfolio', '/portfolio', '▤', ['trainer', 'student', 'dosb', 'dost']],
            ['Learning', 'Learning materials', '/materials', '▶', ['trainer', 'student']],
            ['Learning', 'Media & Engagement', '/media', '★', null],
            ['Staff', 'Staff supervision', '/supervision', '☷', ['dosb', 'dost', 'head']],
            ['Staff', 'Session plans', '/plans', '☰', ['trainer']],
            ['Staff', 'Staff attendance', '/staff-attendance', '✓', ['head', 'dosb', 'dost']],
            ['Communication', 'Announcements', '/announcements', '✉', null],
            ['Operations', 'Fees & parents', '/fees', '$', ['accountant']],
            ['Operations', 'Expenditure', '/expenses', '▤', ['accountant']],
            ['Operations', 'Inventory & assets', '/inventory', '▣', ['storekeeper']],
            ['Operations', 'Calendar & events', '/calendar', '◷', null],
            ['Operations', 'Reports & exports', '/reports', '⇩', ['head', 'dosb', 'dost', 'accountant']],
            ['Operations', 'Documents', '/documents', '▤', ['head', 'dosb', 'dost']],
            ['Admin', 'IT administration', '/admin', '◈', ['admin']],
            ['Admin', 'Website content', '/website', '◐', ['admin']],
            ['Admin', 'Backups', '/backups', '⛁', ['admin']],
            ['Admin', 'Permissions', '/permissions', '⚿', ['admin']],
            ['Account', 'Account settings', '/account', '⚙', null]];
    }
}
