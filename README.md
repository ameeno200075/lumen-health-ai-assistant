# Lumen Health Clinic

Lumen Health Clinic is an AI-powered virtual appointment receptionist built with Laravel 13, React, TypeScript, Inertia.js, Tailwind CSS, and the Laravel AI SDK.

The application helps patients interact with a clinic assistant to find doctors, check availability, manage appointments, and configure appointment reminders.

## Features

- Authenticated patient dashboard and appointment history.
- AI-assisted doctor search and availability checks.
- Appointment booking, cancellation, and rescheduling.
- Appointment reminder preferences and scheduled reminder emails.
- Server-side appointment ownership and administrator authorization.
- Doctor and weekly availability management for administrators.
- UTC-based appointment storage with an explicit `Asia/Damascus` clinic timezone.
- Conversation history scoped to the authenticated user.
- Duplicate-send protection for appointment reminder emails.

## Technology Stack

- **Backend:** PHP 8.3+ and Laravel 13
- **Frontend:** React 19, TypeScript, Inertia.js, and Tailwind CSS
- **Database:** MySQL (production); SQLite supported for local tests
- **Authentication:** Laravel Fortify
- **AI Integration:** Laravel AI SDK with Google Gemini

## Installation

### Prerequisites

Make sure you have the following installed:

- PHP 8.3 or later
- Composer
- Node.js and npm
- MySQL (or SQLite for local testing)

### Setup

Clone the repository and install the dependencies:

```bash
composer install
npm install
```

Create your environment file:

```bash
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database, mail transport, and AI provider credentials in `.env`.

Run the database migrations:

```bash
php artisan migrate
```

Build the frontend assets:

```bash
npm run build
```

**Important:** Never commit your `.env` file, API keys, or other credentials to version control.

## Timezone Configuration

Set the clinic timezone in your `.env` file:

```dotenv
APP_TIMEZONE=Asia/Damascus
```

Appointment instants are stored in UTC. User-entered appointment times, availability checks, reminders, email templates, and React display formatting use the clinic timezone.

This approach avoids manually adding or subtracting hours and keeps appointment handling consistent across the application.

## Running Locally

Start the Laravel development server:

```bash
php artisan serve
```

In a separate terminal, start the frontend development server:

```bash
npm run dev
```

## Scheduled Tasks and Queues

The application uses Laravel's scheduler to send appointment reminders.

For local development, run the scheduler with:

```bash
php artisan schedule:work
```

The reminder command can also be executed manually:

```bash
php artisan app:send-appointment-reminder
```

The reminder command is scheduled to run hourly.

If the selected queue and mail configuration uses asynchronous processing, run a queue worker:

```bash
php artisan queue:work
```

## AI Architecture

The `AppointmentAssistant` coordinates conversations and exposes validated Laravel tools to the AI model.

Gemini does not access the database directly. Instead, the assistant interacts with application functionality through Laravel tools that use application models, database transactions, ownership checks, and appointment availability rules.

Available tools:

- `list_doctors`
- `check_doctor_availability`
- `book_appointment`
- `cancel_appointment`
- `reschedule_appointment`
- `set_appointment_reminder`

Conversation ownership is checked before a conversation is continued or loaded.

Tool failures return safe, user-facing messages, while technical exceptions are logged server-side where appropriate.

## Testing and Quality Checks

Run the automated test suite:

```bash
php artisan test --no-coverage
```

Run the static analysis and frontend checks:

```bash
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run build
```

Appointment tests cover:

- Appointment booking.
- Rebooking after cancellation.
- Ownership-protected cancellation.
- Appointment rescheduling.
- Timezone conversion.
- Reminder eligibility.
- Database-specific concurrency limitations.

## Screenshots

Production screenshots will be added here when the production UI is available.

---

**Lumen Health Clinic** — AI-assisted appointment management built with Laravel.
