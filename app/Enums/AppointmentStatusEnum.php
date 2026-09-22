<?php
namespace App\Enums;
enum AppointmentStatusEnum: string
{
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case FollowUp = 'follow_up';
    case Confirm = 'confirm';
    case InProgress = 'in_progress';
}