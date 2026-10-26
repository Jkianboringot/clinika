import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { CircleAlert } from 'lucide-react';
import { store } from '@/routes/users';
import DentistFields, {
    buildDentistForm,
    dentistPayload,
    DentistForm,
} from '@/components/dentist-fields';

interface UserForm {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
    dentist: DentistForm;
}

interface CreateProps {
    assignableRoles: string[];
}

const ROLE_LABELS: Record<string, string> = {
    dentist: 'Dentist',
    staff: 'Staff',
};

export default function Create({ assignableRoles }: CreateProps) {
    const { data, setData, post, processing, errors, reset, transform } = useForm<UserForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: '',
        dentist: buildDentistForm(),
    });

    // Sends dentist data only when the role is dentist.
    transform((d) => ({
        ...d,
        dentist: d.role === 'dentist' ? dentistPayload(d.dentist) : undefined,
    }));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url, {
            onSuccess: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Create New User" />

            <div className="mx-auto w-full max-w-4xl p-6">
                <div className="mb-4">
                    <h1 className="text-2xl font-bold text-ink">New User</h1>
                    
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-4 rounded-xl border border-[#f0ddc8] bg-white p-5"
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

                    <div className="space-y-1">
                        <Label htmlFor="name" className="font-semibold text-ink">
                            Name
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            placeholder="User name"
                            value={data.name}
                            minLength={3}
                            maxLength={75}
                            onChange={(e) => setData('name', e.target.value)}
                            className="border-[#e0d0c0]"
                        />
                        {errors.name && (
                            <p className="mt-1.5 text-sm text-danger">{errors.name}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
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
                                className="border-[#e0d0c0]"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-sm text-danger">{errors.email}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="role" className="font-semibold text-ink">
                                Role
                            </Label>
                            <Select
                                value={data.role}
                                onValueChange={(value) => setData('role', value)}
                            >
                                <SelectTrigger id="role" className="border-[#e0d0c0]">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    {assignableRoles.map((role) => (
                                        <SelectItem key={role} value={role}>
                                            {ROLE_LABELS[role] ?? role}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.role && (
                                <p className="mt-1.5 text-sm text-danger">{errors.role}</p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1">
                            <Label htmlFor="password" className="font-semibold text-ink">
                                Password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="••••••••"
                                value={data.password}
                                minLength={8}
                                onChange={(e) => setData('password', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                            {errors.password && (
                                <p className="mt-1.5 text-sm text-danger">{errors.password}</p>
                            )}
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="password_confirmation" className="font-semibold text-ink">
                                Confirm password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                value={data.password_confirmation}
                                minLength={8}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                className="border-[#e0d0c0]"
                            />
                        </div>
                    </div>

                    {data.role === 'dentist' && (
                        <DentistFields
                            value={data.dentist}
                            onChange={(dentist) => setData('dentist', dentist)}
                            errors={errors as Record<string, string>}
                        />
                    )}

                    <div className="flex justify-end border-t border-[#f0ddc8] pt-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-brand-orange font-bold text-white hover:bg-brand-orange-hover disabled:opacity-60"
                        >
                            Save User
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        {
            title: 'Create New User',
            href: '/users/create',
        },
    ],
};