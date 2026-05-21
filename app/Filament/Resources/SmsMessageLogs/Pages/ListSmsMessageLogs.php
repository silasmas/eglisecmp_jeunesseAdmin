<?php

namespace App\Filament\Resources\SmsMessageLogs\Pages;

use App\Filament\Resources\SmsMessageLogs\SmsMessageLogResource;
use Filament\Resources\Pages\ListRecords;

class ListSmsMessageLogs extends ListRecords
{
    protected static string $resource = SmsMessageLogResource::class;
}
