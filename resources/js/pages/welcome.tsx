import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarCheck,
    HeartPulse,
    MessageCircle,
    ShieldCheck,
    Sparkles,
} from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function Welcome() {
    return (
        <>
            <Head title="Lumen Health" />
            <div className="min-h-screen bg-background">
                <header className="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 md:px-8">
                    <Link
                        href="/"
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex size-9 items-center justify-center rounded-xl bg-primary text-teal-300">
                            <HeartPulse className="size-5" />
                        </span>
                        Lumen Health
                    </Link>
                    <nav className="flex items-center gap-2">
                        <Button asChild variant="ghost">
                            <Link href="/login">Log in</Link>
                        </Button>
                        <Button asChild>
                            <Link href="/register">Get started</Link>
                        </Button>
                    </nav>
                </header>
                <main>
                    <section className="mx-auto grid max-w-7xl items-center gap-12 px-5 pt-14 pb-20 md:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:pt-24 lg:pb-28">
                        <div>
                            <p className="mb-5 flex items-center gap-2 text-sm font-medium text-teal-700">
                                <Sparkles className="size-4" /> AI-powered
                                clinic care
                            </p>
                            <h1 className="max-w-3xl text-5xl leading-[1.05] font-semibold tracking-tight text-primary md:text-7xl">
                                Your clinic.{' '}
                                <span className="text-teal-600">
                                    One intelligent assistant.
                                </span>
                            </h1>
                            <p className="mt-7 max-w-xl text-lg leading-8 text-muted-foreground">
                                Lumen Health makes healthcare easier to
                                navigate. Find the right doctor, book a visit,
                                and manage your care in one calm, secure place.
                            </p>
                            <div className="mt-9 flex flex-wrap gap-3">
                                <Button asChild size="lg">
                                    <Link href="/register">
                                        Create your account{' '}
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button asChild variant="outline" size="lg">
                                    <Link href="/login">
                                        I already have an account
                                    </Link>
                                </Button>
                            </div>
                            <div className="mt-10 flex flex-wrap gap-5 text-sm text-muted-foreground">
                                <span className="flex items-center gap-2">
                                    <ShieldCheck className="size-4 text-teal-600" />{' '}
                                    Private by design
                                </span>
                                <span className="flex items-center gap-2">
                                    <CalendarCheck className="size-4 text-teal-600" />{' '}
                                    Simple scheduling
                                </span>
                            </div>
                        </div>
                        <div className="relative">
                            <div className="absolute -inset-5 rounded-[2rem] bg-teal-100/60 blur-2xl" />
                            <div className="relative overflow-hidden rounded-[2rem] bg-primary p-6 text-primary-foreground shadow-xl md:p-8">
                                <div className="flex items-center justify-between border-b border-white/10 pb-5">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            Lumen Health
                                        </p>
                                        <p className="mt-1 text-xs text-slate-400">
                                            Your care dashboard
                                        </p>
                                    </div>
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-teal-400 text-slate-950">
                                        <HeartPulse className="size-5" />
                                    </span>
                                </div>
                                <div className="mt-8 rounded-2xl bg-white/10 p-5">
                                    <p className="text-sm text-slate-300">
                                        Good morning
                                    </p>
                                    <p className="mt-1 text-2xl font-semibold">
                                        How can we help?
                                    </p>
                                    <div className="mt-6 grid gap-3">
                                        <div className="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                                            <span className="flex size-9 items-center justify-center rounded-lg bg-teal-400 text-slate-950">
                                                <MessageCircle className="size-4" />
                                            </span>
                                            <span className="text-sm">
                                                Talk to the AI assistant
                                            </span>
                                            <ArrowRight className="ml-auto size-4 text-slate-400" />
                                        </div>
                                        <div className="flex items-center gap-3 rounded-xl bg-white/10 p-3">
                                            <span className="flex size-9 items-center justify-center rounded-lg bg-white/10 text-teal-300">
                                                <CalendarCheck className="size-4" />
                                            </span>
                                            <span className="text-sm">
                                                Manage your appointments
                                            </span>
                                            <ArrowRight className="ml-auto size-4 text-slate-400" />
                                        </div>
                                    </div>
                                </div>
                                <p className="mt-6 text-center text-xs text-slate-400">
                                    Care, coordinated.
                                </p>
                            </div>
                        </div>
                    </section>
                    <section className="border-y bg-card">
                        <div className="mx-auto grid max-w-7xl gap-8 px-5 py-12 md:grid-cols-3 md:px-8">
                            <Feature
                                icon={MessageCircle}
                                title="A helpful first step"
                                text="Ask about doctors, availability, and appointments in natural language."
                            />
                            <Feature
                                icon={CalendarCheck}
                                title="Appointments made simple"
                                text="Book and manage visits with clear times in your clinic timezone."
                            />
                            <Feature
                                icon={ShieldCheck}
                                title="Built around trust"
                                text="Your conversations and appointments stay scoped to your account."
                            />
                        </div>
                    </section>
                </main>
                <footer className="mx-auto max-w-7xl px-5 py-8 text-sm text-muted-foreground md:px-8">
                    © 2026 Lumen Health
                </footer>
            </div>
        </>
    );
}

function Feature({
    icon: Icon,
    title,
    text,
}: {
    icon: typeof MessageCircle;
    title: string;
    text: string;
}) {
    return (
        <div className="flex gap-4">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <Icon className="size-5" />
            </span>
            <div>
                <h2 className="font-semibold">{title}</h2>
                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                    {text}
                </p>
            </div>
        </div>
    );
}
