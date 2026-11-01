# Lumen Health Clinic

Lumen Health Clinic is an AI-powered appointment receptionist built with Laravel 13,
React, TypeScript, Inertia.js, Tailwind CSS, and the Laravel AI SDK.

## Features

- Authenticated patient dashboard and appointment list.
- AI-assisted doctor search, availability checks, booking, cancellation, rescheduling,
  and reminder preferences.
- Server-side appointment ownership and administrator authorization.
- Doctor and weekly availability management for administrators.
- UTC appointment storage with explicit `Asia/Damascus` clinic display timezone.
- Scheduled reminder emails with duplicate-send protection.
- Conversation history scoped to the authenticated user.

## Stack

- PHP 8.3+ and Laravel 13
- React 19, TypeScript, Inertia.js, and Tailwind CSS
- MySQL for production; SQLite is supported for local tests
- Laravel Fortify for authentication
- Laravel AI SDK with Gemini

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Configure `.env` with the database, mail transport, and AI provider settings. Never
commit `.env` or credentials.

The clinic timezone is configured with:

```dotenv
APP_TIMEZONE=Asia/Damascus
```

Appointment instants are stored in UTC. User-entered appointment times, availability
checks, reminders, email templates, and React display formatting use the clinic
timezone without manually adding or subtracting hours.

## Running locally

```bash
php artisan serve
npm run dev
```

Run the scheduler in a production process:

```bash
php artisan schedule:work
```

The reminder command is scheduled hourly:

```bash
php artisan app:send-appointment-reminder
```

Use a queue worker when the selected queue/mail configuration is asynchronous:

```bash
php artisan queue:work
```

## AI architecture

`AppointmentAssistant` coordinates the conversation and exposes validated Laravel
tools. Gemini does not access the database directly. Tools call application models,
transactions, ownership checks, and availability rules:

- `list_doctors`
- `check_doctor_availability`
- `book_appointment`
- `cancel_appointment`
- `reschedule_appointment`
- `set_appointment_reminder`

Conversation ownership is checked before a conversation is continued or loaded.
Tool failures return safe user-facing messages while technical exceptions are logged
server-side where appropriate.

## Testing and quality checks

```bash
php artisan test --no-coverage
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run build
```

Appointment tests cover booking, rebooking after cancellation, ownership-protected
cancellation, rescheduling, timezone conversion, reminder eligibility, and the
database-specific concurrency limitation.

## Screenshots

Add deployment screenshots here when the production UI is available.
