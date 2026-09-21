import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CircleAlert } from 'lucide-react';
import { update } from '@/routes/patients';

interface Patient {
    id: number;
    full_name: string;
    gender: string;
    date_of_birth: string;
    email: string;
    contact_number: string | null;
    emergency_contact_name: string | null;
    emergency_contact_number: string | null;
    address: string | null;
    medical_history: string | null;
    allergies: string | null;
}

interface PatientForm {
    full_name: string;
    gender: string;
    date_of_birth: string;
    email: string;
    password: string;
    password_confirmation: string;
    contact_number: string;
    emergency_contact_name: string;
    emergency_contact_number: string;
    address: string;
    medical_history: string;
    allergies: string;
}

interface Props {
    patient: Patient;
}

// swap these to match your actual GenderEnum cases/labels
const GENDER_LABELS: Record<string, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
};

export default function Edit({ patient }: Props) {
    const { data, setData, put, processing, errors } = useForm<PatientForm>({
        full_name: patient.full_name ?? '',
        gender: patient.gender ?? '',
        date_of_birth: patient.date_of_birth ?? '',
        email: patient.email ?? '',
        password: '',
        password_confirmation: '',
        contact_number: patient.contact_number ?? '',
        emergency_contact_name: patient.emergency_contact_name ?? '',
        emergency_contact_number: patient.emergency_contact_number ?? '',
        address: patient.address ?? '',
        medical_history: patient.medical_history ?? '',
        allergies: patient.allergies ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(patient.id).url);
    };

    return (
        <>
            <Head title={`Edit ${patient.full_name}`} />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">Edit Patient</h1>
                    <p className="mt-1 text-sm text-subtle">{patient.full_name}</p>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-gray-200 bg-white p-5"
                >
                    {Object.keys(errors).length > 0 && (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Something's not right</AlertTitle>
                            <AlertDescription>
                                <ul className="list-inside list-disc text-sm text-danger">
                                    {Object.entries(errors).map(([key, message]) => (
                                        <li key={key}>{message as string}</li>
                                    ))}
                                </ul>
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Identity */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="full_name" className="font-semibold text-ink">
                                Full name
                            </Label>
                            <Input
                                id="full_name"
                                type="text"
                                placeholder="Patient full name"
                                value={data.full_name}
                                minLength={2}
                                maxLength={100}
                                onChange={(e) => setData('full_name', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.full_name && (
                                <p className="mt-1.5 text-sm text-danger">{errors.full_name}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="gender" className="font-semibold text-ink">
                                Gender
                            </Label>
                            <Select
                                value={data.gender}
                                onValueChange={(value) => setData('gender', value)}
                            >
                                <SelectTrigger id="gender" className="border-gray-200">
                                    <SelectValue placeholder="Select gender" />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(GENDER_LABELS).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.gender && (
                                <p className="mt-1.5 text-sm text-danger">{errors.gender}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="date_of_birth" className="font-semibold text-ink">
                                Date of birth
                            </Label>
                            <Input
                                id="date_of_birth"
                                type="date"
                                value={data.date_of_birth}
                                onChange={(e) => setData('date_of_birth', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.date_of_birth && (
                                <p className="mt-1.5 text-sm text-danger">{errors.date_of_birth}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="email" className="font-semibold text-ink">
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="Email address"
                                value={data.email}
                                minLength={3}
                                maxLength={150}
                                onChange={(e) => setData('email', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-sm text-danger">{errors.email}</p>
                            )}
                        </div>
                    </div>

                    {/* Credentials — optional on edit, only sent if filled in */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="password" className="font-semibold text-ink">
                                New password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                placeholder="Leave blank to keep current password"
                                value={data.password}
                                minLength={8}
                                onChange={(e) => setData('password', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.password && (
                                <p className="mt-1.5 text-sm text-danger">{errors.password}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="password_confirmation" className="font-semibold text-ink">
                                Confirm new password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                placeholder="••••••••"
                                value={data.password_confirmation}
                                minLength={8}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="border-gray-200"
                            />
                        </div>
                    </div>

                    {/* Contact */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="contact_number" className="font-semibold text-ink">
                                Contact number
                            </Label>
                            <Input
                                id="contact_number"
                                type="text"
                                placeholder="09XXXXXXXXX"
                                value={data.contact_number}
                                minLength={11}
                                onChange={(e) => setData('contact_number', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.contact_number && (
                                <p className="mt-1.5 text-sm text-danger">{errors.contact_number}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="address" className="font-semibold text-ink">
                                Address
                            </Label>
                            <Input
                                id="address"
                                type="text"
                                placeholder="Street, barangay, city"
                                value={data.address}
                                minLength={3}
                                maxLength={300}
                                onChange={(e) => setData('address', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.address && (
                                <p className="mt-1.5 text-sm text-danger">{errors.address}</p>
                            )}
                        </div>
                    </div>

                    {/* Emergency contact */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="emergency_contact_name" className="font-semibold text-ink">
                                Emergency contact name
                            </Label>
                            <Input
                                id="emergency_contact_name"
                                type="text"
                                placeholder="Contact person's name"
                                value={data.emergency_contact_name}
                                minLength={2}
                                maxLength={100}
                                onChange={(e) => setData('emergency_contact_name', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.emergency_contact_name && (
                                <p className="mt-1.5 text-sm text-danger">{errors.emergency_contact_name}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="emergency_contact_number" className="font-semibold text-ink">
                                Emergency contact number
                            </Label>
                            <Input
                                id="emergency_contact_number"
                                type="text"
                                placeholder="09XXXXXXXXX"
                                value={data.emergency_contact_number}
                                minLength={11}
                                onChange={(e) => setData('emergency_contact_number', e.target.value)}
                                className="border-gray-200"
                            />
                            {errors.emergency_contact_number && (
                                <p className="mt-1.5 text-sm text-danger">{errors.emergency_contact_number}</p>
                            )}
                        </div>
                    </div>

                    {/* Medical */}
                    <div className="space-y-1">
                        <Label htmlFor="medical_history" className="font-semibold text-ink">
                            Medical history
                        </Label>
                        <Textarea
                            id="medical_history"
                            placeholder="Relevant medical history"
                            value={data.medical_history}
                            minLength={3}
                            maxLength={300}
                            onChange={(e) => setData('medical_history', e.target.value)}
                            className="border-gray-200"
                        />
                        {errors.medical_history && (
                            <p className="mt-1.5 text-sm text-danger">{errors.medical_history}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="allergies" className="font-semibold text-ink">
                            Allergies
                        </Label>
                        <Textarea
                            id="allergies"
                            placeholder="Known allergies"
                            value={data.allergies}
                            minLength={3}
                            maxLength={300}
                            onChange={(e) => setData('allergies', e.target.value)}
                            className="border-gray-200"
                        />
                        {errors.allergies && (
                            <p className="mt-1.5 text-sm text-danger">{errors.allergies}</p>
                        )}
                    </div>

                    <div className="flex justify-end border-t border-gray-200 pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-blue-600 font-bold text-white hover:bg-blue-700 disabled:opacity-60"
                        >
                            Save Changes
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Edit Patient',
            href: '/patients/edit',
        },
    ],
};