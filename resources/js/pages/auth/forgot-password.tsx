import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';

export default function ForgotPassword({ status }: { status?: string }) {
    const [step, setStep] = useState<'email' | 'reset'>('email');

    const emailForm = useForm({ email: '' });

    const resetForm = useForm({
        email: '',
        code: '',
        password: '',
        password_confirmation: '',
    });

    const sendCode = (e: React.FormEvent) => {
        e.preventDefault();

        emailForm.post('/forgot-password/code', {
            preserveScroll: true,
            // onSuccess only runs when there are no validation errors
            onSuccess: () => {
                resetForm.setData('email', emailForm.data.email);
                setStep('reset');
            },
        });
    };

    const resendCode = () => {
        emailForm.post('/forgot-password/code', { preserveScroll: true });
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();

        resetForm.post('/forgot-password/reset', {
            onFinish: () => resetForm.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Forgot password" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                {step === 'email' ? (
                    <form onSubmit={sendCode}>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="off"
                                autoFocus
                                placeholder="email@example.com"
                                value={emailForm.data.email}
                                onChange={(e) => emailForm.setData('email', e.target.value)}
                            />
                            <InputError message={emailForm.errors.email} />
                        </div>

                        <div className="my-6 flex items-center justify-start">
                            <Button className="w-full" disabled={emailForm.processing}>
                                {emailForm.processing && (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                )}
                                Send reset code
                            </Button>
                        </div>
                    </form>
                ) : (
                    <form onSubmit={submitReset} className="space-y-5">
                        <p className="text-center text-sm text-muted-foreground">
                            Enter the 6-digit code sent to{' '}
                            <span className="font-medium text-foreground">
                                {resetForm.data.email}
                            </span>
                            .
                        </p>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                autoFocus
                                placeholder="123456"
                                value={resetForm.data.code}
                                onChange={(e) =>
                                    resetForm.setData('code', e.target.value.replace(/\D/g, ''))
                                }
                            />
                            <InputError message={resetForm.errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">New password</Label>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={resetForm.data.password}
                                onChange={(e) => resetForm.setData('password', e.target.value)}
                            />
                            <InputError message={resetForm.errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={resetForm.data.password_confirmation}
                                onChange={(e) =>
                                    resetForm.setData('password_confirmation', e.target.value)
                                }
                            />
                            <InputError message={resetForm.errors.password_confirmation} />
                        </div>

                        <InputError message={resetForm.errors.email} />

                        <Button className="w-full" disabled={resetForm.processing}>
                            {resetForm.processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            Reset password
                        </Button>

                        <div className="flex justify-between text-sm text-muted-foreground">
                            <button
                                type="button"
                                className="underline"
                                onClick={resendCode}
                                disabled={emailForm.processing}
                            >
                                Resend code
                            </button>
                            <button
                                type="button"
                                className="underline"
                                onClick={() => setStep('email')}
                            >
                                Change email
                            </button>
                        </div>
                    </form>
                )}

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Or, return to</span>
                    <TextLink href={login()}>log in</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a reset code',
};