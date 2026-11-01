import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    CalendarCheck,
    CalendarDays,
    CheckCircle2,
    Stethoscope,
    UserRound,
    XCircle,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface Props {
    stats: {
        doctors: number;
        appointments: number;
        upcoming: number;
        confirmed: number;
        cancelled: number;
    };
}

export default function AdminDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Admin overview" />
            <div className="mx-auto w-full max-w-7xl space-y-8 p-5 md:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-teal-700">
                            Clinic operations
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                            Admin overview
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            A live view of your clinic activity.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/doctors">
                            Manage doctors <ArrowRight className="size-4" />
                        </Link>
                    </Button>
                </header>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Metric
                        icon={Stethoscope}
                        label="Doctors"
                        value={stats.doctors}
                    />
                    <Metric
                        icon={CalendarDays}
                        label="All appointments"
                        value={stats.appointments}
                    />
                    <Metric
                        icon={CalendarCheck}
                        label="Upcoming"
                        value={stats.upcoming}
                    />
                    <Metric
                        icon={CheckCircle2}
                        label="Confirmed"
                        value={stats.confirmed}
                        tone="green"
                    />
                    <Metric
                        icon={XCircle}
                        label="Cancelled"
                        value={stats.cancelled}
                        tone="red"
                    />
                </div>
                <Card className="border-border/70 shadow-sm">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Activity className="size-4 text-teal-600" /> Clinic
                            activity
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-xl border border-dashed bg-muted/30 px-6 py-10 text-center text-sm text-muted-foreground">
                            Detailed activity will appear here as your clinic
                            grows.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Metric({
    icon: Icon,
    label,
    value,
    tone = 'teal',
}: {
    icon: typeof UserRound;
    label: string;
    value: number;
    tone?: 'teal' | 'green' | 'red';
}) {
    const colors = {
        teal: 'bg-teal-50 text-teal-700',
        green: 'bg-green-50 text-green-700',
        red: 'bg-red-50 text-red-700',
    };
    return (
        <Card className="border-border/70 shadow-sm">
            <CardContent className="p-5">
                <span
                    className={`mb-4 flex size-9 items-center justify-center rounded-lg ${colors[tone]}`}
                >
                    <Icon className="size-4" />
                </span>
                <p className="text-2xl font-semibold">{value}</p>
                <p className="mt-1 text-xs text-muted-foreground">{label}</p>
            </CardContent>
        </Card>
    );
}
