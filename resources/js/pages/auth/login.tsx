import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

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
        <div className="flex min-h-screen w-full items-center justify-center bg-gray-50 px-6 py-16 font-sans">
            <div className="w-full max-w-md">
                {/* Logo on top, centered */}
                <div className="mb-6 flex flex-col items-center text-center">
                    <img src="/logo.png" alt="Klinika logo" className="h-16 w-16 object-contain" />
                    <div className="mt-3 text-xl font-extrabold leading-tight text-ink">Klinika</div>
                    <div className="text-xs font-medium text-subtle">Dental Care Excellence</div>
                </div>

                {/* Sign in card */}
                <div className="w-full rounded-xl bg-white p-10 shadow-card">
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
                                    className={`w-full rounded-md border-[1.5px] bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-[#1a1a1a] outline-none transition focus:border-[#2f6fed] focus:bg-white ${errors.email ? 'border-[#dc2626]' : 'border-gray-200'
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
                                    className={`w-full rounded-md border-[1.5px] bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-[#1a1a1a] outline-none transition focus:border-[#2f6fed] focus:bg-white ${errors.password ? 'border-[#dc2626]' : 'border-gray-200'
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
                        <div className="h-px flex-1 bg-gray-200" />
                        <span className="text-xs font-medium text-subtle">New to Klinika?</span>
                        <div className="h-px flex-1 bg-gray-200" />
                    </div>

                    <a
                        href="/register"
                        className="mt-4 block w-full rounded-md border-[1.5px] border-gray-200 py-3 text-center text-sm font-bold text-ink transition hover:border-gray-400 hover:text-brand-orange"
                    >
                        Register as New Patient
                    </a>
                </div>
            </div>
        </div>
    );
}