import { Head, useForm } from '@inertiajs/react';

import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function AcceptInvite({
    email,
    acceptUrl,
}: {
    email: string;
    acceptUrl: string;
}) {
    const form = useForm({ name: '', password: '', password_confirmation: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(acceptUrl, {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <Head title="Accept invitation" />
            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="grid gap-2">
                    <Label>Email address</Label>
                    <Input value={email} disabled readOnly />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="name">Your name</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                        autoFocus
                        autoComplete="name"
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput
                        id="password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                        required
                        autoComplete="new-password"
                        placeholder="At least 12 characters, mixed case, number, symbol"
                    />
                    <InputError message={form.errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirm password
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        value={form.data.password_confirmation}
                        onChange={(e) =>
                            form.setData(
                                'password_confirmation',
                                e.target.value,
                            )
                        }
                        required
                        autoComplete="new-password"
                    />
                </div>
                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    Create account
                </Button>
            </form>
        </>
    );
}

AcceptInvite.layout = {
    title: 'Accept your invitation',
    description:
        'Set your name and password to activate your Native Dads Network account',
};
