<?php

namespace App\Enums;

enum Permission: string
{
    case Sales = 'sales';
    case FuelManagement = 'fuel_management';
    case CashFlow = 'cash_flow';
    case SalesAnalysis = 'sales_analysis';
    case Reports = 'reports';
    case Stations = 'stations';
    case ManageTeam = 'manage_team';
    case Customers = 'customers';
    case DeleteRecords = 'delete_records';
}
