# Lumen Health Clinic

<p align="center">
  <strong>An AI-powered appointment receptionist for a modern clinic.</strong>
</p>

Lumen Health Clinic combines a conversational AI assistant with a conventional Laravel application layer. Patients can discover clinic doctors, check real availability, book and manage appointments, and opt in to email reminders. Clinic administrators can manage doctors and their weekly availability.

The AI model does not access the database directly. It uses explicit Laravel tools, where application code validates requests and enforces authorization and booking rules.

## Overview

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** React 19, TypeScript, Inertia.js, Tailwind CSS
- **AI orchestration:** Laravel AI SDK with Google Gemini
- **Authentication:** Laravel Fortify
- **Database:** SQLite by default in the example environment; MySQL can also be configured
- **Clinic timezone:** `Asia/Damascus`; appointment instants are stored in UTC

## Features

### Patient experience

- Authenticated AI receptionist with persistent conversation history.
- Doctor discovery based on clinic records.
- Availability checks against each doctor's configured weekly schedule.
- Appointment booking after the patient confirms the selected doctor and time.
- Appointment history, cancellation, and rescheduling.
- Optional email reminders for upcoming appointments.

### Clinic administration

- Admin-only dashboard.
- Doctor creation, editing, and deletion.
- Weekly doctor availability management.
- Server-side authorization for administrative operations.

### Reliability and safety

- The model cannot query or modify the database directly.
- Laravel tools validate inputs and apply application-level business rules.
- Appointment ownership is checked before patient appointment operations.
- Booking uses database transactions and protects against conflicting bookings.
- Reminder processing uses cache locks and records successful sends to reduce duplicate emails.
- Appointment timestamps are stored in UTC and displayed in the clinic timezone.

## AI assistant architecture

The assistant is implemented in `app/Ai/Agents/AppointmentAssistant.php`. It uses the Laravel AI SDK's conversational capabilities and registers seven application tools.

| Tool | Responsibility |
| --- | --- |
| `list_doctors` | Return doctors available in clinic records. |
| `list_my_appointments` | Retrieve the authenticated patient's appointments. |
| `check_doctor_availability` | Check available slots for a doctor and date. |
| `book_appointment` | Create a booking after confirmation and server-side validation. |
| `cancel_appointment` | Cancel an appointment belonging to the patient. |
| `reschedule_appointment` | Change an eligible appointment after validating the new slot. |
| `set_appointment_reminder` | Store the patient's reminder preference. |

### Request flow

1. The authenticated patient sends a message through the React/Inertia interface.
2. Laravel passes the conversation to `AppointmentAssistant`.
3. Gemini interprets the request and may request one of the registered tools.
4. Laravel executes the selected tool using application models and validation.
5. The tool result is returned to the assistant, which prepares a user-facing response.
6. The application persists the conversation under the authenticated user.

See [the architecture diagram](docs/architecture.svg).

## Appointment lifecycle

- **Discovery:** the assistant retrieves real doctors; it must not invent doctors or specialties.
- **Availability:** candidate slots come from the selected doctor's configured schedule and booking rules.
- **Confirmation:** the assistant repeats the selected doctor and time and waits for the patient's confirmation.
- **Booking:** the Laravel booking tool validates the request and persists the appointment.
- **Management:** the assistant looks up the patient's own appointments before cancellation or rescheduling and asks for confirmation before mutations.
- **Reminder:** patients may opt in to an email reminder approximately 24 hours before the appointment.

## Reminder scheduling

The command `app:send-appointment-reminder` is registered hourly in `routes/console.php`.

Run the scheduler locally:

```bash
php artisan schedule:work
```

Run the reminder command manually:

```bash
php artisan app:send-appointment-reminder
```

The command checks opted-in upcoming appointments, sends the reminder email, and records the send time. A cache lock helps prevent concurrent workers from sending the same reminder simultaneously.

If your queue configuration uses asynchronous jobs, run a queue worker as well:

```bash
php artisan queue:work
```

## Timezone handling

Set the application timezone in `.env`:

```dotenv
APP_TIMEZONE=Asia/Damascus
```

Appointment instants are persisted in UTC. The application interprets patient-facing appointment times in the clinic timezone and converts them at the persistence and presentation boundaries. Avoid manually adding or subtracting timezone offsets.

## Getting started

### Requirements

- PHP 8.3 or later
- Composer
- Node.js and npm
- SQLite or MySQL
- A Google Gemini API key

### Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/ameeno200075/lumen-health-ai-assistant.git
cd lumen-health-ai-assistant
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

On Windows Command Prompt, use:

```bat
copy .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database and add your Gemini API key to `.env`:

```dotenv
GEMINI_API_KEY=your_gemini_api_key
APP_TIMEZONE=Asia/Damascus
```

The example environment uses SQLite. Ensure the SQLite database file exists if your local setup does not create it automatically, then run:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

Start the application in one terminal:

```bash
php artisan serve
```

Start the frontend development server in another terminal:

```bash
npm run dev
```

> Never commit `.env`, API keys, database credentials, or other secrets.

## Testing and quality checks

Run the automated test suite:

```bash
php artisan test --no-coverage
```

Run the available static and frontend checks:

```bash
vendor/bin/phpstan analyse --no-progress
npm run types:check
npm run check
npm run build
```

The test suite includes coverage for appointment workflows, authorization/ownership, timezone behavior, reminder eligibility, and database-dependent booking behavior. Exact test coverage depends on the current test files and configured database driver.

## Screenshots

The repository includes a screenshot checklist in [docs/screenshots/README.md](docs/screenshots/README.md). Add genuine screenshots from the running application there; do not use mockups as evidence of implemented functionality.

## Project structure

```text
app/
  Ai/
    Agents/       # Conversational appointment assistant
    Tools/        # Validated application actions exposed to the AI
  Console/        # Scheduled reminder command
  Http/           # Controllers and request handling
  Mail/            # Appointment reminder email
  Models/          # Doctors, appointments, availability, users
docs/
  architecture.svg
  screenshots/
```

## Security notes

- Keep API keys and environment-specific credentials outside version control.
- Keep patient and administrator operations behind authentication and authorization.
- Treat AI-generated tool arguments as untrusted input; validate them in Laravel.
- Do not rely on the model alone to enforce appointment ownership, availability, or confirmation.

---

**Lumen Health Clinic** — conversational appointment management backed by explicit application rules.
