<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function redirectToHomeView()
    {
        return redirect()->route('homeView');
    }

    public function homeView()
    {
        $data = [];

        /* =========================
           Leave Types
        ========================== */
        $types = LeaveType::all();
        $data['types'] = $types;

        /* =========================
           Leave Used (Approved Only)
        ========================== */
        $apps = LeaveApplication::myApplications()
            ->where('status', 'approved')
            ->addLeaveType()
            ->get();

        foreach ($types as $type) {
            $data['leaveCount'][$type->type] = 0;
        }

        foreach ($apps as $app) {
            $data['leaveCount'][$app->type] += $app->duration;
        }

        /* =========================
           My Applications (Paginated)
        ========================== */
        $data['myApplications'] = LeaveApplication::myApplications()
            ->addLeaveType()
            ->paginate(5);

        /* =========================
           Leave Statistics (FIXED)
        ========================== */
        $data['leaveStat'] = LeaveApplication::myApplications()
            ->reorder() // ✅ removes hidden ORDER BY from scope
            ->select('status', DB::raw('COUNT(status) AS total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pages.home', $data);
    }

    public function leaveApplicationView()
    {
        $data['leaveTypes'] = LeaveType::all();
        return view('pages.application', $data);
    }

    public function actionView()
    {
        $data['applications'] = LeaveApplication::where('status', 'pending')
            ->join('users', 'users.id', '=', 'leave_applications.applier_user_id')
            ->join('leave_types', 'leave_types.id', '=', 'leave_applications.leave_type_id')
            ->select(
                'leave_applications.id as id',
                'leave_applications.reason',
                'leave_applications.information',
                'users.name as applier_name',
                'leave_applications.start_date',
                'leave_applications.end_date',
                'leave_applications.status',
                'leave_types.type as leave_type',
                'leave_applications.created_at'
            )
            ->get();

        return view('pages.action', $data);
    }

    public function NotificationView()
    {
        return view('pages.notification');
    }

    public function markAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return redirect()->route('homeView');
    }
}
