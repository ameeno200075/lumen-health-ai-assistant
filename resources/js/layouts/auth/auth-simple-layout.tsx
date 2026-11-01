import { Link } from '@inertiajs/react';
import { HeartPulse, ShieldCheck, Sparkles } from 'lucide-react';

import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[1.05fr_0.95fr]">
            <section className="relative hidden overflow-hidden bg-primary p-12 text-primary-foreground lg:flex lg:flex-col lg:justify-between">
                <div className="absolute -top-32 -right-32 size-96 rounded-full border border-teal-300/20" />
                <div className="absolute -bottom-40 -left-20 size-[28rem] rounded-full border border-teal-300/10" />
                <Link
                    href="/"
                    className="relative flex items-center gap-3 text-lg font-semibold"
                >
                    <span className="flex size-10 items-center justify-center rounded-xl bg-teal-400 text-slate-950">
                        <HeartPulse className="size-5" />
                    </span>
                    Lumen Health
                </Link>
                <div className="relative max-w-lg">
                    <p className="mb-5 text-sm font-medium tracking-[0.2em] text-teal-300 uppercase">
                        Your clinic. One intelligent assistant.
                    </p>
                    <h2 className="text-5xl leading-tight font-semibold tracking-tight">
                        Healthcare that feels personal.
                    </h2>
                    <p className="mt-6 max-w-md text-base leading-7 text-slate-300">
                        Find the right doctor, manage your visits, and get
                        support from a trusted AI receptionist.
                    </p>
                    <div className="mt-10 flex gap-6 text-sm text-slate-300">
                        <span className="flex items-center gap-2">
                            <Sparkles className="size-4 text-teal-300" />{' '}
                            AI-powered care
                        </span>
                        <span className="flex items-center gap-2">
                            <ShieldCheck className="size-4 text-teal-300" />{' '}
                            Private and secure
                        </span>
                    </div>
                </div>
                <p className="relative text-xs text-slate-400">
                    Lumen Health · Care, coordinated.
                </p>
            </section>
            <main className="flex items-center justify-center p-6 md:p-12">
                <div className="w-full max-w-sm">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4 lg:items-start">
                            <Link
                                href="/"
                                className="flex items-center gap-2 font-semibold lg:hidden"
                            >
                                <span className="flex size-8 items-center justify-center rounded-lg bg-teal-500 text-white">
                                    <HeartPulse className="size-4" />
                                </span>
                                Lumen Health
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    {title}
                                </h1>
                                <p className="text-center text-sm text-muted-foreground lg:text-left">
                                    {description}
                                </p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
