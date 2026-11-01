const CLINIC_TIMEZONE = 'Asia/Damascus';

export function formatAppointmentTime(value: string): string {
    return new Intl.DateTimeFormat(undefined, {
        timeZone: CLINIC_TIMEZONE,
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}
