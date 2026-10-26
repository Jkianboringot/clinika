import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function PasswordResetSection() {
    const [codeSent, setCodeSent] = useState(false);

    const sendForm = useForm({});
    const resetForm = useForm({
        code: '',
        password: '',
        password_confirmation: '',
    });

    const sendCode = () => {
        sendForm.post('/settings/security/password-code', {
            preserveScroll: true,
            onSuccess: () => setCodeSent(true),
        });
    };

    const submitReset = (e: React.FormEvent) => {
        e.preventDefault();
        resetForm.post('/settings/security/password-reset', {
            preserveScroll: true,
            onSuccess: () => {
                resetForm.reset();
                setCodeSent(false);
            },
            onError: () => resetForm.reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="space-y-4">
            <div>
                <h3 className="text-base font-medium">Password</h3>
                <p className="text-sm text-muted-foreground">
                    To change your password, we'll email a code to your account email.
                </p>
            </div>

            {!codeSent && (
                <>
                    <Button type="button" onClick={sendCode} disabled={sendForm.processing}>
                        {sendForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Forgot password
                    </Button>
                    <InputError message={(sendForm.errors as Record<string, string>).email} />
                </>
            )}

            {codeSent && (
                <form onSubmit={submitReset} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="code">6-digit code</Label>
                        <Input
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
                        />
                        <InputError message={resetForm.errors.code} />
                        <p className="text-xs text-muted-foreground">
                            The code expires in 10 minutes.
                        </p>
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
                        <Label htmlFor="password_confirmation">Confirm new password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={resetForm.data.password_confirmation}
                            onChange={(e) =>
                                resetForm.setData('password_confirmation', e.target.value)
                            }
                        />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={resetForm.processing}>
                            {resetForm.processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            Change password
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={sendCode}
                            disabled={sendForm.processing}
                        >
                            Resend code
                        </Button>
                    </div>
                </form>
            )}
        </div>
    );
}