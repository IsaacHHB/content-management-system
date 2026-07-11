import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type ContactProps = {
    seo: Seo;
};

export default function Contact({ seo }: ContactProps) {
    const settings = usePage().props.settings as
        Record<string, string | null> | undefined;

    const [startedAt] = useState(() => Math.floor(Date.now() / 1000));
    const form = useForm({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        website: '',
        form_started_at: startedAt,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post('/contact', {
            preserveScroll: true,
            onSuccess: () =>
                form.reset('name', 'email', 'phone', 'subject', 'message'),
        });
    };

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">
                    Contact Us
                </h1>

                <div className="mt-8 grid gap-10 lg:grid-cols-3">
                    <form
                        onSubmit={submit}
                        className="space-y-5 lg:col-span-2"
                        noValidate
                    >
                        {/* Honeypot field — left blank by humans, hidden from assistive tech and sighted users. */}
                        <div
                            className="absolute left-[-9999px] h-0 w-0 overflow-hidden"
                            aria-hidden="true"
                        >
                            <label htmlFor="website">Website</label>
                            <input
                                id="website"
                                type="text"
                                name="website"
                                tabIndex={-1}
                                autoComplete="off"
                                value={form.data.website}
                                onChange={(e) =>
                                    form.setData('website', e.target.value)
                                }
                            />
                        </div>

                        <div>
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                aria-invalid={Boolean(form.errors.name)}
                                className="mt-1"
                                required
                            />
                            {form.errors.name && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.name}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) =>
                                        form.setData('email', e.target.value)
                                    }
                                    aria-invalid={Boolean(form.errors.email)}
                                    className="mt-1"
                                    required
                                />
                                {form.errors.email && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {form.errors.email}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                    aria-invalid={Boolean(form.errors.phone)}
                                    className="mt-1"
                                />
                                {form.errors.phone && (
                                    <p className="mt-1 text-sm text-red-600">
                                        {form.errors.phone}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="subject">Subject</Label>
                            <Input
                                id="subject"
                                value={form.data.subject}
                                onChange={(e) =>
                                    form.setData('subject', e.target.value)
                                }
                                aria-invalid={Boolean(form.errors.subject)}
                                className="mt-1"
                                required
                            />
                            {form.errors.subject && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.subject}
                                </p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="message">Message</Label>
                            <Textarea
                                id="message"
                                rows={6}
                                value={form.data.message}
                                onChange={(e) =>
                                    form.setData('message', e.target.value)
                                }
                                aria-invalid={Boolean(form.errors.message)}
                                className="mt-1"
                                required
                            />
                            {form.errors.message && (
                                <p className="mt-1 text-sm text-red-600">
                                    {form.errors.message}
                                </p>
                            )}
                        </div>

                        <Button
                            type="submit"
                            disabled={form.processing}
                            size="lg"
                        >
                            Send message
                        </Button>
                    </form>

                    <aside className="h-fit rounded-lg border p-5">
                        <h2 className="font-semibold">Get in touch</h2>
                        <dl className="mt-3 space-y-2 text-sm">
                            {settings?.contact_email && (
                                <div>
                                    <dt className="text-neutral-500">Email</dt>
                                    <dd>
                                        <a
                                            href={`mailto:${settings.contact_email}`}
                                            className="hover:underline"
                                        >
                                            {settings.contact_email}
                                        </a>
                                    </dd>
                                </div>
                            )}
                            {settings?.contact_phone && (
                                <div>
                                    <dt className="text-neutral-500">Phone</dt>
                                    <dd>
                                        <a
                                            href={`tel:${settings.contact_phone}`}
                                            className="hover:underline"
                                        >
                                            {settings.contact_phone}
                                        </a>
                                    </dd>
                                </div>
                            )}
                            {settings?.mailing_address && (
                                <div>
                                    <dt className="text-neutral-500">
                                        Mailing address
                                    </dt>
                                    <dd className="whitespace-pre-line">
                                        {settings.mailing_address}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </aside>
                </div>
            </div>
        </>
    );
}
