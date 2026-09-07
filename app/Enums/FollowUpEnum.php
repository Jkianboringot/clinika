<?php

enum FollowUpIntervalEnum: string
{
    case One_Week = '1_week';
    case Two_Weeks = '2_weeks';
    case One_Month = '1_month';
    case Two_Months = '2_months';
    case Three_Months = '3_months';
    case Six_Months = '6_months';
    case One_Year = '1_year';
    case AsNeeded = 'as_needed';
}