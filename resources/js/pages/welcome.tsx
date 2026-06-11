import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

const FEATURES = [
    {
        icon: '⛽',
        title: 'Fuel Inventory',
        description: 'Monitor tank levels, track deliveries, and get low-stock alerts before you run dry.',
    },
    {
        icon: '💰',
        title: 'Sales & Revenue',
        description: 'Real-time pump sales data, shift totals, and daily reconciliation in one view.',
    },
    {
        icon: '👥',
        title: 'Staff Shifts',
        description: 'Schedule attendants, log shift handovers, and track individual performance easily.',
    },
    {
        icon: '📋',
        title: 'Expense Tracking',
        description: 'Record operational costs, maintenance, and petty cash with a clear audit trail.',
    },
    {
        icon: '📈',
        title: 'Reports & Analytics',
        description: 'Export daily, weekly, or monthly reports to PDF or Excel with one click.',
    },
    {
        icon: '🔒',
        title: 'Role-based Access',
        description: 'Owner, manager, and attendant roles — each sees exactly what they need.',
    },
];

const STATS = [
    { icon: '⛽', label: 'Fuel Tracking', value: 'Real-time' },
    { icon: '📊', label: 'Daily Reports', value: 'Auto-generated' },
    { icon: '👥', label: 'Staff Management', value: 'Shift-based' },
    { icon: '🔒', label: 'Security', value: 'Role-based access' },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />

            <div className="min-h-screen bg-[#0f172a] text-white">
                {/* ── Hero ─────────────────────────────── */}
                <section className="relative flex min-h-screen flex-col overflow-hidden">
                    {/* Background image + gradient overlay */}
                    <div
                        className="absolute inset-0 bg-cover bg-center"
                        style={{ backgroundImage: "url('/images/petrol-pumps-banner.webp')" }}
                    >
                        <div className="absolute inset-0 bg-gradient-to-br from-[#0f172a]/95 via-[#0f172a]/75 to-[#0f172a]/40" />
                    </div>

                    {/* Navbar */}
                    <nav className="relative z-10 flex items-center justify-between border-b border-white/8 bg-[#0f172a]/35 px-6 py-5 backdrop-blur-md lg:px-10">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-lg">
                                ⛽
                            </span>
                            <span className="text-[1.05rem] font-bold tracking-tight">
                                GS <span className="text-amber-400">Management</span>
                            </span>
                        </div>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg border border-white/25 px-5 py-2 text-sm font-medium transition hover:border-white/40 hover:bg-white/8"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-lg border border-white/22 px-5 py-2 text-sm font-medium transition hover:border-white/38 hover:bg-white/8"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={login()}
                                        className="rounded-lg bg-amber-500 px-5 py-2 text-sm font-bold text-gray-900 shadow-[0_2px_12px_rgba(245,158,11,.35)] transition hover:-translate-y-px hover:bg-amber-600"
                                    >
                                        Get Started →
                                    </Link>
                                </>
                            )}
                        </div>
                    </nav>

                    {/* Hero content */}
                    <div className="relative z-10 flex flex-1 items-center px-6 py-16 lg:px-10">
                        <div className="mx-auto w-full max-w-5xl">
                            <div className="max-w-[560px]">
                                <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-amber-400/35 bg-amber-500/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-amber-400">
                                    ⚡ Smart Station Operations
                                </div>

                                <h1 className="mb-5 text-[clamp(2.4rem,5vw,3.6rem)] font-extrabold leading-[1.1] tracking-tight text-white">
                                    Manage Your
                                    <br />
                                    <span className="text-amber-400">Gas Station</span>
                                    <br />
                                    With Confidence
                                </h1>

                                <p className="mb-10 max-w-[460px] text-[1.05rem] leading-relaxed text-slate-300">
                                    A complete management platform for gas station owners — track fuel
                                    inventory, monitor sales, manage staff shifts, and generate instant
                                    reports from one clean dashboard.
                                </p>

                                <div className="flex flex-wrap items-center gap-4">
                                    <Link
                                        href={login()}
                                        className="rounded-xl bg-amber-500 px-8 py-3.5 text-base font-bold text-gray-900 shadow-[0_2px_16px_rgba(245,158,11,.4)] transition hover:-translate-y-px hover:bg-amber-600"
                                    >
                                        Get Started Free
                                    </Link>
                                    <a
                                        href="#features"
                                        className="rounded-xl border border-white/30 px-7 py-3.5 text-base font-medium transition hover:bg-white/8"
                                    >
                                        See Features
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Stats strip */}
                    <div className="relative z-10 flex flex-wrap gap-6 px-6 pb-12 lg:px-10">
                        <div className="mx-auto flex w-full max-w-5xl flex-wrap gap-6">
                            {STATS.map((stat) => (
                                <div key={stat.label} className="flex items-center gap-3">
                                    <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-lg">
                                        {stat.icon}
                                    </span>
                                    <div>
                                        <div className="text-xs text-slate-400">{stat.label}</div>
                                        <div className="text-sm font-bold text-white">{stat.value}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ── Features ─────────────────────────── */}
                <section id="features" className="bg-[#1e293b] px-6 py-20 lg:px-10">
                    <div className="mx-auto max-w-5xl">
                        <p className="mb-3 text-center text-xs font-bold uppercase tracking-[.1em] text-amber-400">
                            Everything You Need
                        </p>
                        <h2 className="mb-3 text-center text-[2rem] font-extrabold text-white">
                            Built for Gas Station Teams
                        </h2>
                        <p className="mx-auto mb-12 max-w-md text-center leading-relaxed text-slate-400">
                            From daily fuel reconciliation to monthly profit analysis, every tool is right
                            where you need it.
                        </p>

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {FEATURES.map((feature) => (
                                <div
                                    key={feature.title}
                                    className="group rounded-2xl border border-white/8 bg-white/4 p-7 transition duration-200 hover:-translate-y-1 hover:border-amber-400/35"
                                >
                                    <span className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/12 text-2xl">
                                        {feature.icon}
                                    </span>
                                    <h3 className="mb-2 text-[1.05rem] font-bold text-white">
                                        {feature.title}
                                    </h3>
                                    <p className="text-sm leading-relaxed text-slate-400">
                                        {feature.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ── Footer ───────────────────────────── */}
                <footer className="border-t border-white/6 bg-[#0f172a] px-6 py-8 text-center text-sm text-slate-500">
                    © {new Date().getFullYear()} GS Management · Built with care for station operators.
                </footer>
            </div>
        </>
    );
}
