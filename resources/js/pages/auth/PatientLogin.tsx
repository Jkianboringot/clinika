import { FormEvent, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
} from 'lucide-react';
import FlashError from '@/components/flash-error';




export default function PatientLogin() {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        post('/patient/login');
    };

    return (
        <>
            <Head title="Patient Portal" />

            <div className="relative min-h-screen overflow-hidden bg-[#faf9fc]">
                {/* Purple ambient background */}
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-purple-200/40 blur-3xl" />
                    <div className="absolute -right-32 top-1/4 h-96 w-96 rounded-full bg-violet-200/30 blur-3xl" />
                    <div className="absolute bottom-[-180px] left-1/3 h-96 w-96 rounded-full bg-purple-100/50 blur-3xl" />
                </div>

                <div className="relative flex min-h-screen items-center justify-center px-5 py-10">
                    <div className="w-full max-w-md">

                        {/* Logo */}
                        <div className="mb-8 flex justify-center">
                            <div className="flex h-20 w-20 items-center justify-center rounded-[24px] bg-white shadow-[0_12px_40px_rgba(88,28,135,0.12)] ring-1 ring-purple-100">
                                <img
                                    src="/logo.png"
                                    alt="Klinika"
                                    className="h-14 w-14 object-contain"
                                />
                            </div>
                        </div>

                        {/* Heading */}
                        <div className="mb-8 text-center">
                            <p className="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-purple-600">
                                Klinika
                            </p>

                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">
                                Welcome back
                            </h1>

                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Sign in to access your patient portal.
                            </p>
                        </div>

                        {/* Login Card */}
                        <div className="rounded-[28px] border border-purple-100/80 bg-white p-7 shadow-[0_20px_60px_rgba(88,28,135,0.10)] sm:p-8">
 <FlashError />
                            <form onSubmit={submit} className="space-y-5">

                                {/* Email */}
                                <div>
                                    <label
                                        htmlFor="email"
                                        className="mb-2 block text-sm font-semibold text-gray-800"
                                    >
                                        Email address
                                    </label>

                                    <div className="group relative">
                                        <Mail
                                            size={19}
                                            className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 transition group-focus-within:text-purple-600"
                                        />

                                        <input
                                            id="email"
                                            type="email"
                                            autoComplete="email"
                                            value={data.email}
                                            onChange={(e) =>
                                                setData('email', e.target.value)
                                            }
                                            placeholder="you@example.com"
                                            className="h-13 w-full rounded-2xl border border-gray-200 bg-gray-50/70 pl-12 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-purple-500 focus:bg-white focus:ring-4 focus:ring-purple-500/10"
                                        />
                                    </div>

                                    {errors.email && (
                                        <p className="mt-2 text-xs font-medium text-red-500">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {/* Password */}
                                <div>
                                    <div className="mb-2 flex items-center justify-between">
                                        <label
                                            htmlFor="password"
                                            className="block text-sm font-semibold text-gray-800"
                                        >
                                            Password
                                        </label>
                                    </div>

                                    <div className="group relative">
                                        <LockKeyhole
                                            size={19}
                                            className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 transition group-focus-within:text-purple-600"
                                        />

                                        <input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(e) =>
                                                setData('password', e.target.value)
                                            }
                                            placeholder="Enter your password"
                                            className="h-13 w-full rounded-2xl border border-gray-200 bg-gray-50/70 pl-12 pr-12 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-purple-500 focus:bg-white focus:ring-4 focus:ring-purple-500/10"
                                        />

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowPassword(!showPassword)
                                            }
                                            className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-purple-600"
                                            aria-label={
                                                showPassword
                                                    ? 'Hide password'
                                                    : 'Show password'
                                            }
                                        >
                                            {showPassword ? (
                                                <EyeOff size={19} />
                                            ) : (
                                                <Eye size={19} />
                                            )}
                                        </button>
                                    </div>

                                    {errors.password && (
                                        <p className="mt-2 text-xs font-medium text-red-500">
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                {/* Remember */}
                                <label className="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) =>
                                            setData(
                                                'remember',
                                                e.target.checked,
                                            )
                                        }
                                        className="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                    />

                                    <span className="text-sm text-gray-600">
                                        Keep me signed in
                                    </span>
                                </label>

                                {/* Submit */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="group flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-purple-600 px-5 text-sm font-bold text-white shadow-[0_10px_25px_rgba(147,51,234,0.22)] transition hover:bg-purple-700 hover:shadow-[0_12px_30px_rgba(147,51,234,0.28)] focus:outline-none focus:ring-4 focus:ring-purple-500/20 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span>
                                        {processing
                                            ? 'Signing in...'
                                            : 'Sign in to Patient Portal'}
                                    </span>

                                    {!processing && (
                                        <ArrowRight
                                            size={18}
                                            className="transition-transform group-hover:translate-x-1"
                                        />
                                    )}
                                </button>
                            </form>

                            {/* Staff Login */}
                            <div className="mt-7 border-t border-gray-100 pt-6 text-center">
                                <p className="text-xs text-gray-400">
                                    Are you a staff member?
                                </p>

                                <a
                                    href="/login"
                                    className="mt-2 inline-block text-sm font-semibold text-purple-600 transition hover:text-purple-800"
                                >
                                    Sign in as Staff →
                                </a>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="mt-7 text-center">
                            <p className="text-[10px] font-semibold uppercase tracking-[0.2em] text-gray-400">
                                Secure Patient Portal
                            </p>

                            <p className="mt-2 text-xs text-gray-400">
                                © {new Date().getFullYear()} Klinika
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}