import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

function StethoscopeIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <path
                d="M6 3v6a4 4 0 0 0 8 0V3M6 5H4.5M14 5h1.5M18 9v2a6 6 0 0 1-12 0V9"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
            />
            <circle cx="19" cy="15" r="2.2" stroke="currentColor" strokeWidth="1.6" />
        </svg>
    );
}

function MailIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" strokeWidth="1.6" />
            <path d="m4 7 8 6 8-6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function LockIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" strokeWidth="1.6" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
        </svg>
    );
}

const stats = [
    { value: '500+', label: 'Active Patients' },
    { value: '1000+', label: 'Appointments' },
    { value: '98%', label: 'Satisfaction' },
    { value: '24/7', label: 'Online Access' },
];

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/login');
    }

    return (
        <div className="flex min-h-screen w-full font-sans">
            {/* Left panel — brand / value prop */}
            <div className="relative hidden w-1/2 flex-col justify-center overflow-hidden bg-gradient-to-br from-brand-orange to-brand-orange-light px-14 py-16 text-white lg:flex">
                <div className="pointer-events-none absolute -right-32 -top-32 h-[420px] w-[420px] rounded-full bg-white/10" />
                <div className="pointer-events-none absolute -bottom-24 -left-16 h-[320px] w-[320px] rounded-full bg-black/5" />

                <div className="relative z-10 mb-10 flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white">
                        <StethoscopeIcon className="h-6 w-6 text-brand-orange" />
                    </div>
                    <div>
                        <div className="text-lg font-extrabold leading-tight">  Klinika</div>
                        <div className="text-xs font-medium text-white/80">Dental Care Excellence</div>
                    </div>
                </div>

                <h1 className="relative z-10 max-w-md text-4xl font-extrabold leading-tight tracking-tight">
                    Integrated Clinic Management System
                </h1>
                <p className="relative z-10 mt-4 max-w-md text-sm font-medium leading-relaxed text-white/85">
                    Streamline your dental practice with automated notifications, inventory
                    monitoring, and comprehensive patient care management.
                </p>

                <div className="relative z-10 mt-10 grid max-w-md grid-cols-2 gap-4">
                    {stats.map((stat) => (
                        <div key={stat.label} className="rounded-xl bg-white/15 px-5 py-4 backdrop-blur-sm">
                            <div className="text-2xl font-extrabold">{stat.value}</div>
                            <div className="text-xs font-medium text-white/85">{stat.label}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Right panel — sign in form */}
            <div className="flex w-full items-center justify-center bg-cream px-6 py-16 lg:w-1/2">
                <div className="w-full max-w-md rounded-xl bg-white p-10 shadow-card lg:border-t-0 lg:shadow-none">
                    <div className="mb-2 flex items-center gap-3 lg:hidden">
                        <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-orange">
                            <StethoscopeIcon className="h-5 w-5 text-white" />
                        </div>
                        <div className="text-lg font-extrabold text-ink">  Klinika</div>
                    </div>

                    <h2 className="text-2xl font-extrabold tracking-tight text-ink">Welcome Back</h2>
                    <p className="mt-1 text-sm font-medium text-subtle">Sign in to access your account</p>

                    {status && (
                        <div className="mt-5 rounded-md border border-success/30 border-l-4 border-l-success bg-success/5 px-4 py-3 text-sm font-medium text-[#276749]">
                            {status}
                        </div>
                    )}
                    <form onSubmit={submit} className="mt-6 space-y-4" style={{ colorScheme: 'light' }}>
                        <div>
                            <label className="mb-1.5 block text-xs font-semibold tracking-wide text-[#1a1a1a]">
                                Email Address
                            </label>
                            <div className="relative">
                                <MailIcon className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9a9a9a]" />
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className={`w-full rounded-md border-[1.5px] bg-[#fdfaf7] py-2.5 pl-10 pr-4 text-sm text-[#1a1a1a] outline-none transition focus:border-[#2f6fed] focus:bg-white ${errors.email ? 'border-[#dc2626]' : 'border-[#e0d0c0]'
                                        }`}
                                    placeholder="you@example.com"
                                />
                            </div>
                            {errors.email && <p className="mt-1 text-xs font-medium text-[#dc2626]">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="mb-1.5 block text-xs font-semibold tracking-wide text-[#1a1a1a]">
                                Password
                            </label>
                            <div className="relative">
                                <LockIcon className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#9a9a9a]" />
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className={`w-full rounded-md border-[1.5px] bg-[#fdfaf7] py-2.5 pl-10 pr-4 text-sm text-[#1a1a1a] outline-none transition focus:border-[#2f6fed] focus:bg-white ${errors.password ? 'border-[#dc2626]' : 'border-[#e0d0c0]'
                                        }`}
                                    placeholder="••••••••"
                                />
                            </div>
                            {errors.password && (
                                <p className="mt-1 text-xs font-medium text-[#dc2626]">{errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-between pt-1">
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 accent-[#f97316]"
                                />
                                <span className="text-sm font-medium text-[#666]">Remember me</span>
                            </label>
                            {/* <a
                            href="/forgot-password"
                            className="text-xs font-medium text-[#f97316] hover:underline"
        >
                            Forgot password?
                        </a> */}
                </div>

                <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-md bg-brand-orange py-3 text-sm font-bold tracking-wide text-white transition hover:-translate-y-px hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Sign In
                        </button>
            </form>

            <div className="mt-6 flex items-center gap-3">
                <div className="h-px flex-1 bg-[#e0d0c0]" />
                <span className="text-xs font-medium text-subtle">New to   Klinika?</span>
                <div className="h-px flex-1 bg-[#e0d0c0]" />
            </div>

            <a
                href="/register"
                className="mt-4 block w-full rounded-md border-[1.5px] border-[#e0d0c0] py-3 text-center text-sm font-bold text-ink transition hover:border-brand-orange hover:text-brand-orange"
            >
                Register as New Patient
            </a>
        </div>
            </div >
        </div >
    );
}