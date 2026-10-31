<?php
namespace App\Enums;
enum IsApproveEnum: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Pending = 'pending';
    case PendingReScheduled = 'pending_reschedule';

}