import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

function BurgerIcon({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
            <path d="M4 9c0-3.3 3.6-6 8-6s8 2.7 8 6H4Z" fill="currentColor" />
            <rect x="3.5" y="10.5" width="17" height="2" rx="1" fill="currentColor" />
            <path
                d="M4 14h16c0 .9-.4 1.7-1.1 2.3.5.4.8 1 .8 1.7 0 1.4-1.6 2.5-3.5 2.5H7.8c-1.9 0-3.5-1.1-3.5-2.5 0-.7.3-1.3.8-1.7A2.9 2.9 0 0 1 4 14Z"
                fill="currentColor"
            />
            <rect x="3.5" y="12.2" width="17" height="1.4" rx="0.7" fill="currentColor" opacity="0.6" />
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
        <div className="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-cream p-6 font-sans">
            {/* <div className="pointer-events-none absolute -right-24 -top-24 h-[500px] w-[500px] rounded-full bg-[radial-gradient(circle,hsla(30,100%,50%,0.12)_0%,transparent_70%)]" />
            <div className="pointer-events-none absolute -bottom-20 -left-20 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,hsla(30,100%,50%,0.08)_0%,transparent_70%)]" /> */}

            <div className="relative z-10 w-full rounded-xl border-t-4 border-brand-orange bg-white p-12">
                <div className="mb-8 text-center">
                    <div className="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-lg bg-brand-orange">
                        <BurgerIcon className="h-7 w-7 text-white" />
                    </div>
                    <div className="text-xl font-extrabold tracking-tight text-ink">MB</div>
                    <div className="mt-1 text-sm font-medium text-subtle">Sign in to your account</div>
                </div>

                {status && (
                    <div className="mb-5 rounded-md border border-success/30 border-l-4 border-l-success bg-success/5 px-4 py-3 text-sm font-medium text-[#276749]">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="mx-auto max-w-md space-y-4">
                    <div>
                        <label className="mb-1.5 block text-xs font-semibold tracking-wide text-ink">
                            Email Address
                        </label>
                        <input
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className={`w-full rounded-md border-[1.5px] bg-[#fdfaf7] px-4 py-2.5 text-sm outline-none transition focus:border-brand-orange focus:bg-white ${errors.email ? 'border-danger' : 'border-[#e0d0c0]'
                                }`}
                            placeholder="you@example.com"
                        />
                        {errors.email && <p className="mt-1 text-xs font-medium text-danger">{errors.email}</p>}
                    </div>

                    <div>
                        <label className="mb-1.5 block text-xs font-semibold tracking-wide text-ink">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className={`w-full rounded-md border-[1.5px] bg-[#fdfaf7] px-4 py-2.5 text-sm outline-none transition focus:border-brand-orange focus:bg-white ${errors.password ? 'border-danger' : 'border-[#e0d0c0]'
                                }`}
                            placeholder="••••••••"
                        />
                        {errors.password && <p className="mt-1 text-xs font-medium text-danger">{errors.password}</p>}
                    </div>

                    <label className="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                            className="h-4 w-4 accent-brand-orange"
                        />
                        <span className="text-sm font-medium text-[#666]">Remember me</span>
                    </label>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full rounded-md bg-brand-orange py-3 text-sm font-bold tracking-wide text-white transition hover:-translate-y-px hover:bg-brand-orange-hover disabled:opacity-60"
                    >
                        Sign In
                    </button>
                </form>

                <div className="mt-5 text-center">

                    <a href="/forgot-password"
                        className="text-xs font-medium text-subtle hover:text-brand-orange hover:underline"
                    >
                        Forgot your password?
                    </a>
                </div>
            </div>
        </div>
    );
}