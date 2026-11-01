import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarCheck,
    CalendarPlus,
    MessageCircle,
    Search,
    Sparkles,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

export default function Dashboard() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const firstName = auth.user?.name?.split(' ')[0] ?? 'there';

    return (
        <>
            <Head title="Overview" />
            <div className="mx-auto w-full max-w-7xl space-y-8 p-5 md:p-8">
                <section className="relative overflow-hidden rounded-3xl bg-primary p-7 text-primary-foreground md:p-10">
                    <div className="absolute -top-32 -right-20 size-80 rounded-full border border-teal-300/20" />
                    <div className="absolute right-24 -bottom-40 size-96 rounded-full border border-teal-300/10" />
                    <div className="relative max-w-2xl">
                        <div className="mb-4 flex items-center gap-2 text-sm text-teal-300">
                            <Sparkles className="size-4" /> Lumen Health
                            assistant
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                            Good morning, {firstName}.
                        </h1>
                        <p className="mt-3 max-w-lg text-sm leading-6 text-slate-300 md:text-base">
                            Your care, coordinated. Ask anything about finding a
                            doctor, booking a visit, or managing your
                            appointments.
                        </p>
                        <Button
                            asChild
                            className="mt-7 bg-teal-400 text-slate-950 hover:bg-teal-300"
                        >
                            <Link href="/assistant">
                                Talk to the assistant{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </section>

                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Your care at a glance
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Everything you need for your next visit.
                            </p>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <QuickAction
                            href="/assistant"
                            icon={MessageCircle}
                            title="AI assistant"
                            description="Get help in seconds"
                        />
                        <QuickAction
                            href="/assistant"
                            icon={CalendarPlus}
                            title="Book a visit"
                            description="Find an available slot"
                        />
                        <QuickAction
                            href="/appointments"
                            icon={CalendarCheck}
                            title="My appointments"
                            description="View and manage visits"
                        />
                        <QuickAction
                            href="/assistant"
                            icon={Search}
                            title="Find a doctor"
                            description="Browse clinic specialists"
                        />
                    </div>
                </section>

                <div className="grid gap-5 lg:grid-cols-[1.4fr_1fr]">
                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Upcoming appointment
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex min-h-40 flex-col items-center justify-center rounded-2xl border border-dashed bg-muted/30 px-6 text-center">
                                <CalendarCheck className="mb-3 size-8 text-teal-600" />
                                <p className="font-medium">
                                    No upcoming appointments
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    When you book a visit, it will appear here.
                                </p>
                                <Button
                                    asChild
                                    variant="outline"
                                    size="sm"
                                    className="mt-4"
                                >
                                    <Link href="/assistant">Find a time</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-border/70 shadow-sm">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Helpful information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <InfoRow
                                title="Clinic timezone"
                                detail="Asia/Damascus (UTC+03:00)"
                            />
                            <InfoRow
                                title="Need urgent care?"
                                detail="For severe symptoms, contact emergency services."
                            />
                            <Link
                                href="/assistant"
                                className="inline-flex items-center gap-1 font-medium text-teal-700 hover:text-teal-800"
                            >
                                Ask the assistant{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

function QuickAction({
    href,
    icon: Icon,
    title,
    description,
}: {
    href: string;
    icon: typeof MessageCircle;
    title: string;
    description: string;
}) {
    return (
        <Link
            href={href}
            className="group rounded-2xl border border-border/70 bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md"
        >
            <span className="mb-4 flex size-10 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <Icon className="size-5" />
            </span>
            <p className="font-medium">{title}</p>
            <p className="mt-1 text-sm text-muted-foreground">{description}</p>
            <ArrowRight className="mt-4 size-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-teal-700" />
        </Link>
    );
}

function InfoRow({ title, detail }: { title: string; detail: string }) {
    return (
        <div>
            <p className="font-medium">{title}</p>
            <p className="mt-1 text-muted-foreground">{detail}</p>
        </div>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Overview', href: dashboard() }] };
