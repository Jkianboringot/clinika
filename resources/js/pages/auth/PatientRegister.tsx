import { FormEvent, ReactNode, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff } from 'lucide-react';
import FlashError from '@/components/flash-error';

// Match the options to your GenderEnum cases
const GENDER_LABELS: Record<string, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
};

const inputClass =
    'h-12 w-full rounded-2xl border border-gray-200 bg-gray-50/70 px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-purple-500 focus:bg-white focus:ring-4 focus:ring-purple-500/10';

const textareaClass =
    'w-full rounded-2xl border border-gray-200 bg-gray-50/70 px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-purple-500 focus:bg-white focus:ring-4 focus:ring-purple-500/10';

function Field({
    label,
    htmlFor,
    error,
    optional,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    optional?: boolean;
    children: ReactNode;
}) {
    return (
        <div>
            <label htmlFor={htmlFor} className="mb-2 block text-sm font-semibold text-gray-800">
                {label}
                {optional && <span className="ml-1 font-normal text-gray-400">(optional)</span>}
            </label>
            {children}
            {error && <p className="mt-2 text-xs font-medium text-red-500">{error}</p>}
        </div>
    );
}

function Section({ title, hint, children }: { title: string; hint?: string; children: ReactNode }) {
    return (
        <fieldset className="space-y-4">
            <div>
                <legend className="text-base font-bold text-gray-900">{title}</legend>
                {hint && <p className="mt-0.5 text-xs text-gray-500">{hint}</p>}
            </div>
            {children}
        </fieldset>
    );
}

export default function PatientRegister() {
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        date_of_birth: '',
        email: '',
        contact_number: '',
        address: '',
        emergency_contact_name: '',
        emergency_contact_number: '',
        medical_history: '',
        allergies: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        post('/patient/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Create your account" />

            <div className="relative min-h-screen overflow-hidden bg-[#faf9fc]">
                <div className="pointer-events-none absolute inset-0">
                    <div className="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-purple-200/40 blur-3xl" />
                    <div className="absolute -right-32 top-1/4 h-96 w-96 rounded-full bg-violet-200/30 blur-3xl" />
                    <div className="absolute bottom-[-180px] left-1/3 h-96 w-96 rounded-full bg-purple-100/50 blur-3xl" />
                </div>

                <div className="relative flex min-h-screen items-center justify-center px-5 py-10">
                    <div className="w-full max-w-2xl">
                        {/* Logo */}
                        <div className="mb-6 flex justify-center">
                            <div className="flex h-20 w-20 items-center justify-center rounded-[24px] bg-white shadow-[0_12px_40px_rgba(88,28,135,0.12)] ring-1 ring-purple-100">
                                <img src="/logo.png" alt="Klinika" className="h-14 w-14 object-contain" />
                            </div>
                        </div>

                        <div className="mb-8 text-center">
                            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Create your account</h1>
                            <p className="mt-2 text-sm leading-6 text-gray-500">
                                Register as a patient to book appointments and view your treatment records.
                            </p>
                        </div>

                        <div className="rounded-[28px] border border-purple-100/80 bg-white p-7 shadow-[0_20px_60px_rgba(88,28,135,0.10)] sm:p-8">
                            <FlashError />

                            <form onSubmit={submit} className="space-y-8">
                                {/* About you */}
                                <Section title="About you">
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        <Field label="First name" htmlFor="first_name" error={errors.first_name}>
                                            <input
                                                id="first_name"
                                                type="text"
                                                autoComplete="given-name"
                                                value={data.first_name}
                                                onChange={(e) => setData('first_name', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                        <Field label="Middle name" htmlFor="middle_name" error={errors.middle_name} optional>
                                            <input
                                                id="middle_name"
                                                type="text"
                                                autoComplete="additional-name"
                                                value={data.middle_name}
                                                onChange={(e) => setData('middle_name', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                        <Field label="Last name" htmlFor="last_name" error={errors.last_name}>
                                            <input
                                                id="last_name"
                                                type="text"
                                                autoComplete="family-name"
                                                value={data.last_name}
                                                onChange={(e) => setData('last_name', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Gender" htmlFor="gender" error={errors.gender}>
                                            <select
                                                id="gender"
                                                value={data.gender}
                                                onChange={(e) => setData('gender', e.target.value)}
                                                className={inputClass}
                                            >
                                                <option value="">Select gender</option>
                                                {Object.entries(GENDER_LABELS).map(([value, label]) => (
                                                    <option key={value} value={value}>
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                        </Field>
                                        <Field label="Date of birth" htmlFor="date_of_birth" error={errors.date_of_birth}>
                                            <input
                                                id="date_of_birth"
                                                type="date"
                                                autoComplete="bday"
                                                value={data.date_of_birth}
                                                max={new Date().toISOString().split('T')[0]}
                                                onChange={(e) => setData('date_of_birth', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                {/* Contact */}
                                <Section title="Contact details">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Mobile number" htmlFor="contact_number" error={errors.contact_number}>
                                            <input
                                                id="contact_number"
                                                type="tel"
                                                inputMode="numeric"
                                                autoComplete="tel"
                                                placeholder="09XXXXXXXXX"
                                                maxLength={11}
                                                value={data.contact_number}
                                                onChange={(e) => setData('contact_number', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                        <Field label="Address" htmlFor="address" error={errors.address} optional>
                                            <input
                                                id="address"
                                                type="text"
                                                autoComplete="street-address"
                                                placeholder="Street, barangay, city"
                                                value={data.address}
                                                onChange={(e) => setData('address', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                {/* Account */}
                                <Section title="Sign-in details" hint="You'll use these to sign in to the patient portal.">
                                    <Field label="Email address" htmlFor="email" error={errors.email}>
                                        <input
                                            id="email"
                                            type="email"
                                            autoComplete="email"
                                            placeholder="you@example.com"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className={inputClass}
                                        />
                                    </Field>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Password" htmlFor="password" error={errors.password}>
                                            <div className="relative">
                                                <input
                                                    id="password"
                                                    type={showPassword ? 'text' : 'password'}
                                                    autoComplete="new-password"
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                    className={`${inputClass} pr-12`}
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setShowPassword(!showPassword)}
                                                    className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-purple-600"
                                                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                                                >
                                                    {showPassword ? <EyeOff size={19} /> : <Eye size={19} />}
                                                </button>
                                            </div>
                                        </Field>
                                        <Field
                                            label="Confirm password"
                                            htmlFor="password_confirmation"
                                            error={errors.password_confirmation}
                                        >
                                            <input
                                                id="password_confirmation"
                                                type={showPassword ? 'text' : 'password'}
                                                autoComplete="new-password"
                                                value={data.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                {/* Health + emergency (optional) */}
                                <Section
                                    title="Health information"
                                    hint="Optional now, but it helps your dentist prepare for your visit."
                                >
                                    <Field label="Allergies" htmlFor="allergies" error={errors.allergies} optional>
                                        <textarea
                                            id="allergies"
                                            rows={2}
                                            maxLength={300}
                                            placeholder="e.g. penicillin, latex"
                                            value={data.allergies}
                                            onChange={(e) => setData('allergies', e.target.value)}
                                            className={textareaClass}
                                        />
                                    </Field>
                                    <Field label="Medical history" htmlFor="medical_history" error={errors.medical_history} optional>
                                        <textarea
                                            id="medical_history"
                                            rows={2}
                                            maxLength={300}
                                            placeholder="Conditions, medications, past surgeries"
                                            value={data.medical_history}
                                            onChange={(e) => setData('medical_history', e.target.value)}
                                            className={textareaClass}
                                        />
                                    </Field>
                                </Section>

                                <Section title="Emergency contact" hint="Optional. Fill in both fields or leave both blank.">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Contact name"
                                            htmlFor="emergency_contact_name"
                                            error={errors.emergency_contact_name}
                                            optional
                                        >
                                            <input
                                                id="emergency_contact_name"
                                                type="text"
                                                value={data.emergency_contact_name}
                                                onChange={(e) => setData('emergency_contact_name', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                        <Field
                                            label="Contact number"
                                            htmlFor="emergency_contact_number"
                                            error={errors.emergency_contact_number}
                                            optional
                                        >
                                            <input
                                                id="emergency_contact_number"
                                                type="tel"
                                                inputMode="numeric"
                                                placeholder="09XXXXXXXXX"
                                                maxLength={11}
                                                value={data.emergency_contact_number}
                                                onChange={(e) => setData('emergency_contact_number', e.target.value)}
                                                className={inputClass}
                                            />
                                        </Field>
                                    </div>
                                </Section>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="group flex h-13 w-full items-center justify-center gap-2 rounded-2xl bg-purple-600 px-5 text-sm font-bold text-white shadow-[0_10px_25px_rgba(147,51,234,0.22)] transition hover:bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-500/20 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <span>{processing ? 'Creating account...' : 'Create account'}</span>
                                    {!processing && (
                                        <ArrowRight size={18} className="transition-transform group-hover:translate-x-1" />
                                    )}
                                </button>
                            </form>

                            <div className="mt-7 border-t border-gray-100 pt-6 text-center">
                                <p className="text-xs text-gray-400">Already have an account?</p>
                                <Link
                                    href="/patient/login"
                                    className="mt-2 inline-block text-sm font-semibold text-purple-600 transition hover:text-purple-800"
                                >
                                    Sign in
                                </Link>
                            </div>
                        </div>

                        <p className="mt-7 text-center text-xs text-gray-400">© {new Date().getFullYear()} Klinika</p>
                    </div>
                </div>
            </div>
        </>
    );
}