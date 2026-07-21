<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Job;

class CalendarController extends Controller
{
    private const MONTH_NAMES_TR = [
        1 => 'Ocak', 2 => 'Subat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayis', 6 => 'Haziran',
        7 => 'Temmuz', 8 => 'Agustos', 9 => 'Eylul', 10 => 'Ekim', 11 => 'Kasim', 12 => 'Aralik',
    ];

    public function index(): void
    {
        $year = (int) ($this->input('year') ?: date('Y'));
        $month = (int) ($this->input('month') ?: date('n'));

        // Clamp to valid month range and let year roll over naturally via DateTime
        if ($month < 1 || $month > 12) {
            $month = date('n');
        }

        $jobs = Auth::can('customers.view')
            ? Job::allForMonth($year, $month)
            : array_filter(Job::allForMonth($year, $month), function ($job) {
                return $this->currentUserIsAssigned($job['id']);
            });

        // Group jobs by day-of-month for quick lookup while rendering
        $jobsByDay = [];
        foreach ($jobs as $job) {
            $day = (int) date('j', strtotime($job['start_date']));
            $jobsByDay[$day][] = $job;
        }

        // Appointments (site-visit/inspection) — leads/estimator-only info,
        // same visibility rule as the rest of the customer/project data.
        // Employees never see these (they don't have customers.view either).
        $appointmentsByDay = [];
        if (Auth::can('customers.view')) {
            foreach (Appointment::allForMonth($year, $month) as $appointment) {
                $day = (int) date('j', strtotime($appointment['scheduled_date']));
                $appointmentsByDay[$day][] = $appointment;
            }
        }

        $firstOfMonth = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $firstOfMonth);
        // PHP's 'N' gives 1 (Mon) .. 7 (Sun); we render Pazartesi-first weeks
        $startWeekday = (int) date('N', $firstOfMonth);

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        echo View::renderWithLayout('calendar/index', [
            'year' => $year,
            'month' => $month,
            'monthName' => self::MONTH_NAMES_TR[$month],
            'daysInMonth' => $daysInMonth,
            'startWeekday' => $startWeekday,
            'jobsByDay' => $jobsByDay,
            'appointmentsByDay' => $appointmentsByDay,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
            'todayDay' => ($year == date('Y') && $month == date('n')) ? (int) date('j') : null,
        ]);
    }

    private function currentUserIsAssigned(int $jobId): bool
    {
        foreach (Job::employeesForJob($jobId) as $employee) {
            if ((int) $employee['id'] === (int) Auth::id()) {
                return true;
            }
        }
        return false;
    }
}
