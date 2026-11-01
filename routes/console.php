<?php

use Illuminate\Support\Facades\Schedule;

// Send appointment reminder emails every hour.
Schedule::command('app:send-appointment-reminder')->hourly();
