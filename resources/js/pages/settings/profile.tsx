import { Form, Head, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

// Staff users have `name`; patients have the split name columns.
type ProfileUser = {
    name?: string;
    first_name?: string;
    middle_name?: string | null;
    last_name?: string;
    full_name?: string;
    email: string;
    avatar_url?: string | null;
};

const getDisplayName = (user: ProfileUser): string =>
    user.full_name ??
    user.name ??
    [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ');

const getInitials = (user: ProfileUser): string =>
    (user.name ?? '')
        .split(' ')
        .filter(Boolean)
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();

export default function Profile() {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user as unknown as ProfileUser;
    const isPatient = Boolean(user.first_name);
    const displayName = getDisplayName(user);

    const inputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);

    useEffect(() => {
        return () => {
            if (preview) URL.revokeObjectURL(preview);
        };
    }, [preview]);

    const clearSelection = () => {
        setPreview(null);
        if (inputRef.current) inputRef.current.value = '';
    };

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            {/* Profile photo: staff only, patients don't get one */}
            {!isPatient && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Profile photo"
                        description="Upload a JPG, PNG, or WebP image (max 2MB)"
                    />

                    <Form
                        {...ProfileController.updateAvatar.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={clearSelection}
                        className="space-y-4"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="flex items-center gap-4">
                                    <Avatar className="h-20 w-20">
                                        <AvatarImage
                                            src={preview ?? user.avatar_url ?? undefined}
                                            alt={displayName}
                                            className="object-cover"
                                        />
                                        <AvatarFallback className="text-xl">
                                            {getInitials(user)}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <input
                                            ref={inputRef}
                                            type="file"
                                            name="avatar"
                                            accept="image/png,image/jpeg,image/webp"
                                            className="hidden"
                                            onChange={(e) => {
                                                const file = e.target.files?.[0];
                                                setPreview(file ? URL.createObjectURL(file) : null);
                                            }}
                                        />

                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => inputRef.current?.click()}
                                        >
                                            Choose photo
                                        </Button>

                                        {preview && (
                                            <>
                                                <Button
                                                    disabled={processing}
                                                    data-test="upload-avatar-button"
                                                >
                                                    {processing ? 'Uploading...' : 'Save photo'}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={clearSelection}
                                                >
                                                    Cancel
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>

                                <InputError message={errors.avatar} />
                            </>
                        )}
                    </Form>

                    {user.avatar_url && !preview && (
                        <Form
                            {...ProfileController.destroyAvatar.form()}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing }) => (
                                <Button
                                    variant="ghost"
                                    className="text-red-600"
                                    disabled={processing}
                                    data-test="remove-avatar-button"
                                >
                                    Remove photo
                                </Button>
                            )}
                        </Form>
                    )}
                </div>
            )}

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {isPatient ? (
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="first_name">First name</Label>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            defaultValue={user.first_name ?? ''}
                                            required
                                            maxLength={100}
                                            autoComplete="given-name"
                                            placeholder="First name"
                                        />
                                        <InputError message={errors.first_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="middle_name">Middle name</Label>
                                        <Input
                                            id="middle_name"
                                            name="middle_name"
                                            defaultValue={user.middle_name ?? ''}
                                            maxLength={100}
                                            autoComplete="additional-name"
                                            placeholder="Middle name (optional)"
                                        />
                                        <InputError message={errors.middle_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="last_name">Last name</Label>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            defaultValue={user.last_name ?? ''}
                                            required
                                            maxLength={100}
                                            autoComplete="family-name"
                                            placeholder="Last name"
                                        />
                                        <InputError message={errors.last_name} />
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError className="mt-2" message={errors.name} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError className="mt-2" message={errors.email} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};