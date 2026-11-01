<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new reminder email for the given appointment.
     */
    public function __construct(public Appointment $appointment)
    {
        //
    }

    /**
     * Define the email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Your Upcoming Appointment at Lumen Health Clinic',
        );
    }

    /**
     * Define the email template and the data passed to it.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.appointments.reminder',
            with: [
                'appointment' => $this->appointment,
            ],
        );
    }

    /**
     * Define any email attachments.
     */
    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
