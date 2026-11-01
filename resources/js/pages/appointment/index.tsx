import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    BellRing,
    CalendarDays,
    CalendarRange,
    Clock3,
    Stethoscope,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Appointment } from '@/types/clinic';
import { formatAppointmentTime } from '@/lib/appointment-time';

interface Props {
    appointments: Appointment[];
}

export default function AppointmentsIndex({ appointments }: Props) {
    const [rescheduling, setRescheduling] = useState<Appointment | null>(null);
    const form = useForm({ date: '', time: '', scheduled_at: '' });
    const upcoming = appointments.filter((appointment) =>
        ['booked', 'confirmed'].includes(appointment.status),
    );
    const history = appointments.filter(
        (appointment) => !['booked', 'confirmed'].includes(appointment.status),
    );

    function cancel(appointment: Appointment) {
        if (
            !window.confirm(
                `Cancel your appointment with ${appointment.doctor.name}?`,
            )
        )
            return;
        router.post(
            `/appointments/${appointment.id}/cancel`,
            {},
            { onSuccess: () => toast.success('Appointment cancelled.') },
        );
    }

    function openReschedule(appointment: Appointment) {
        setRescheduling(appointment);
        form.reset();
        form.clearErrors();
    }

    function submitReschedule(event: React.FormEvent) {
        event.preventDefault();
        if (!rescheduling) return;
        form.put(`/appointments/${rescheduling.id}/reschedule`, {
            onSuccess: () => {
                setRescheduling(null);
                toast.success('Appointment rescheduled.');
            },
        });
    }

    return (
        <>
            <Head title="My appointments" />
            <div className="mx-auto w-full max-w-6xl space-y-8 p-5 md:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-teal-700">
                            Care management
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                            My appointments
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Keep track of upcoming visits and your care history.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/assistant">
                            <CalendarRange className="size-4" /> Book a visit
                        </Link>
                    </Button>
                </header>

                <AppointmentSection
                    title="Upcoming"
                    icon={CalendarDays}
                    appointments={upcoming}
                    empty="You have no upcoming appointments."
                >
                    {(appointment) => (
                        <AppointmentCard
                            appointment={appointment}
                            onCancel={() => cancel(appointment)}
                            onReschedule={() => openReschedule(appointment)}
                        />
                    )}
                </AppointmentSection>
                <AppointmentSection
                    title="History"
                    icon={Clock3}
                    appointments={history}
                    empty="Completed and cancelled visits will appear here."
                >
                    {(appointment) => (
                        <AppointmentCard appointment={appointment} />
                    )}
                </AppointmentSection>
            </div>

            <Dialog
                open={Boolean(rescheduling)}
                onOpenChange={(open) => !open && setRescheduling(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reschedule appointment</DialogTitle>
                        <DialogDescription>
                            Choose a new date and time. The clinic timezone is
                            Asia/Damascus.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitReschedule} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="reschedule-date">Date</Label>
                            <Input
                                id="reschedule-date"
                                type="date"
                                value={form.data.date}
                                onChange={(event) =>
                                    form.setData('date', event.target.value)
                                }
                                required
                            />
                            {form.errors.date && (
                                <p className="text-sm text-destructive">
                                    {form.errors.date}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reschedule-time">Time</Label>
                            <Input
                                id="reschedule-time"
                                type="time"
                                value={form.data.time}
                                onChange={(event) =>
                                    form.setData('time', event.target.value)
                                }
                                required
                            />
                            {form.errors.time && (
                                <p className="text-sm text-destructive">
                                    {form.errors.time}
                                </p>
                            )}
                            {form.errors.scheduled_at && (
                                <p className="text-sm text-destructive">
                                    {form.errors.scheduled_at}
                                </p>
                            )}
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setRescheduling(null)}
                            >
                                Keep current time
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Saving…' : 'Reschedule'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

function AppointmentSection({
    title,
    icon: Icon,
    appointments,
    empty,
    children,
}: {
    title: string;
    icon: typeof CalendarDays;
    appointments: Appointment[];
    empty: string;
    children: (appointment: Appointment) => React.ReactNode;
}) {
    return (
        <section>
            <div className="mb-4 flex items-center gap-2">
                <Icon className="size-5 text-teal-600" />
                <h2 className="text-lg font-semibold">{title}</h2>
                <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                    {appointments.length}
                </span>
            </div>
            {appointments.length === 0 ? (
                <div className="rounded-2xl border border-dashed bg-card px-6 py-10 text-center text-sm text-muted-foreground">
                    {empty}
                </div>
            ) : (
                <div className="grid gap-4 md:grid-cols-2">
                    {appointments.map((appointment) => (
                        <div key={appointment.id}>{children(appointment)}</div>
                    ))}
                </div>
            )}
        </section>
    );
}

function AppointmentCard({
    appointment,
    onCancel,
    onReschedule,
}: {
    appointment: Appointment;
    onCancel?: () => void;
    onReschedule?: () => void;
}) {
    return (
        <article className="rounded-2xl border border-border/70 bg-card p-5 shadow-sm transition hover:shadow-md">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="flex size-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                        <Stethoscope className="size-5" />
                    </span>
                    <div>
                        <h3 className="font-semibold">
                            {appointment.doctor.name}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {appointment.doctor.specialty}
                        </p>
                    </div>
                </div>
                <StatusBadge status={appointment.status} />
            </div>
            <div className="mt-5 grid gap-3 rounded-xl bg-muted/50 p-4 text-sm sm:grid-cols-2">
                <div>
                    <p className="text-xs text-muted-foreground">
                        Date and time
                    </p>
                    <p className="mt-1 font-medium">
                        {formatAppointmentTime(appointment.scheduled_at)}
                    </p>
                </div>
                {appointment.reminder_opt_in && (
                    <div className="flex items-end gap-1 text-xs text-teal-700">
                        <BellRing className="size-3.5" /> Reminder enabled
                    </div>
                )}
            </div>
            {appointment.reason && (
                <p className="mt-4 text-sm text-muted-foreground">
                    Reason: {appointment.reason}
                </p>
            )}
            {onCancel && (
                <div className="mt-5 flex flex-wrap justify-end gap-2">
                    <Button variant="outline" size="sm" onClick={onReschedule}>
                        <CalendarRange className="size-4" /> Reschedule
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive hover:text-destructive"
                        onClick={onCancel}
                    >
                        <X className="size-4" /> Cancel
                    </Button>
                </div>
            )}
        </article>
    );
}

function StatusBadge({ status }: { status: string }) {
    const style =
        status === 'confirmed'
            ? 'bg-green-50 text-green-700'
            : status === 'cancelled'
              ? 'bg-red-50 text-red-700'
              : 'bg-amber-50 text-amber-700';
    return (
        <Badge variant="outline" className={`capitalize ${style}`}>
            {status}
        </Badge>
    );
}
