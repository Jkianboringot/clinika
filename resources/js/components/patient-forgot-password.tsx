import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { ArrowLeft, KeyRound, LoaderCircle, LockKeyhole, Mail } from 'lucide-react';

type Props = {
    onBack: () => void;
    onDone: () => void;
};

const inputClass =
    'h-13 w-full rounded-2xl border border-gray-200 bg-gray-50/70 pl-12 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-purple-500 focus:bg-white focus:ring-4 focus:ring-purple-500/10';
const iconClass =
    'pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 transition group-focus-within:text-purple-600';
const errorClass = 'mt-2 text-xs font-medium text-red-500';
const labelClass = 'mb-2 block text-sm font-semibold text-gray-800';

export default function PatientForgotPassword({ onBack, onDone }: Props) {
    const [codeSent, setCodeSent] = useState(false);

    const sendForm = useForm({ email: '' });
    const resetForm = useForm({
        email: '',
        code: '',
        password: '',
        password_confirmation: '',
    });

    const sendCode = (e?: FormEvent) => {
        e?.preventDefault();

        sendForm.post('/patient/forgot-password/code', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                resetForm.setData('email', sendForm.data.email);
                setCodeSent(true);
            },
        });
    };

    const submitReset = (e: FormEvent) => {
        e.preventDefault();

        resetForm.post('/patient/forgot-password/reset', {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                resetForm.reset();
                onDone();
            },
            onError: () => resetForm.reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="space-y-5">
            <button
                type="button"
                onClick={onBack}
                className="flex items-center gap-1 text-sm font-semibold text-purple-600 hover:text-purple-800"
            >
                <ArrowLeft size={16} /> Back to sign in
            </button>

            <div>
                <h2 className="text-xl font-bold text-gray-900">Forgot password?</h2>
                <p className="mt-1 text-sm text-gray-500">
                    {codeSent
                        ? 'Enter the 6-digit code we emailed you and choose a new password.'
                        : "Enter your email and we'll send you a 6-digit code."}
                </p>
            </div>

            {!codeSent ? (
                <form onSubmit={sendCode} className="space-y-5">
                    <div>
                        <label htmlFor="reset_email" className={labelClass}>
                            Email address
                        </label>
                        <div className="group relative">
                            <Mail size={19} className={iconClass} />
                            <input
                                id="reset_email"
                                type="email"
                                autoComplete="email"
                                autoFocus
                                value={sendForm.data.email}
                                onChange={(e) => sendForm.setData('email', e.target.value)}
                                placeholder="you@example.com"
                                className={inputClass}
                            />
                        </div>
                        {sendForm.errors.email && <p className={errorClass}>{sendForm.errors.email}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={sendForm.processing}
                        className="flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-purple-600 px-5 text-sm font-bold text-white shadow-[0_10px_25px_rgba(147,51,234,0.22)] transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {sendForm.processing && <LoaderCircle size={18} className="animate-spin" />}
                        Send code
                    </button>
                </form>
            ) : (
                <form onSubmit={submitReset} className="space-y-5">
                    <div>
                        <label htmlFor="code" className={labelClass}>6-digit code</label>
                        <div className="group relative">
                            <KeyRound size={19} className={iconClass} />
                            <input
                                id="code"
                                inputMode="numeric"
                                maxLength={6}
                                autoComplete="one-time-code"
                                autoFocus
                                value={resetForm.data.code}
                                onChange={(e) =>
                                    resetForm.setData('code', e.target.value.replace(/\D/g, ''))
                                }
                                placeholder="123456"
                                className={inputClass}
                            />
                        </div>
                        {resetForm.errors.code && <p className={errorClass}>{resetForm.errors.code}</p>}
                        <p className="mt-2 text-xs text-gray-400">The code expires in 10 minutes.</p>
                    </div>

                    <div>
                        <label htmlFor="new_password" className={labelClass}>New password</label>
                        <div className="group relative">
                            <LockKeyhole size={19} className={iconClass} />
                            <input
                                id="new_password"
                                type="password"
                                autoComplete="new-password"
                                value={resetForm.data.password}
                                onChange={(e) => resetForm.setData('password', e.target.value)}
                                className={inputClass}
                            />
                        </div>
                        {resetForm.errors.password && <p className={errorClass}>{resetForm.errors.password}</p>}
                    </div>

                    <div>
                        <label htmlFor="new_password_confirmation" className={labelClass}>
                            Confirm new password
                        </label>
                        <div className="group relative">
                            <LockKeyhole size={19} className={iconClass} />
                            <input
                                id="new_password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={resetForm.data.password_confirmation}
                                onChange={(e) =>
                                    resetForm.setData('password_confirmation', e.target.value)
                                }
                                className={inputClass}
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            type="submit"
                            disabled={resetForm.processing}
                            className="flex h-13 flex-1 items-center justify-center gap-2 rounded-2xl bg-purple-600 px-5 text-sm font-bold text-white shadow-[0_10px_25px_rgba(147,51,234,0.22)] transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {resetForm.processing && <LoaderCircle size={18} className="animate-spin" />}
                            Change password
                        </button>
                        <button
                            type="button"
                            onClick={() => sendCode()}
                            disabled={sendForm.processing}
                            className="h-13 rounded-2xl px-4 text-sm font-semibold text-purple-600 hover:bg-purple-50 disabled:opacity-60"
                        >
                            Resend code
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}