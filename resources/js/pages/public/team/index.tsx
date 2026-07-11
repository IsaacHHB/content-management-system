import { Link } from '@inertiajs/react';

import { SeoHead } from '@/components/public/seo-head';
import type { Seo } from '@/components/public/seo-head';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type MemberCard = {
    id: number;
    name: string;
    slug: string;
    title: string;
    photo_url: string | null;
};

type Group = {
    key: string;
    label: string;
    members: MemberCard[];
};

type TeamIndexProps = {
    groups: Group[];
    seo: Seo;
};

function MemberGrid({ members }: { members: MemberCard[] }) {
    if (members.length === 0) {
        return (
            <p className="mt-6 text-sm text-neutral-500">
                No members to show yet.
            </p>
        );
    }

    return (
        <div className="mt-8 grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-4">
            {members.map((member) => (
                <Link
                    key={member.id}
                    href={`/about/team/${member.slug}`}
                    className="group text-center"
                >
                    {member.photo_url ? (
                        <img
                            src={member.photo_url}
                            alt={member.name}
                            className="mx-auto size-32 rounded-full object-cover transition group-hover:opacity-90"
                        />
                    ) : (
                        <div className="mx-auto flex size-32 items-center justify-center rounded-full bg-neutral-200 text-2xl font-semibold text-neutral-500">
                            {member.name.charAt(0)}
                        </div>
                    )}
                    <p className="mt-3 font-semibold group-hover:underline">
                        {member.name}
                    </p>
                    <p className="text-sm text-neutral-500">{member.title}</p>
                </Link>
            ))}
        </div>
    );
}

export default function TeamIndex({ groups, seo }: TeamIndexProps) {
    const first =
        groups.find((g) => g.members.length > 0)?.key ?? groups[0]?.key;

    return (
        <>
            <SeoHead seo={seo} />

            <div className="mx-auto max-w-6xl px-4 py-12">
                <h1 className="text-3xl font-bold tracking-tight">Our Team</h1>
                <p className="mt-2 max-w-2xl text-neutral-600">
                    The people behind the Native Dads Network — our staff and
                    our board of directors.
                </p>

                <Tabs defaultValue={first} className="mt-8">
                    <TabsList>
                        {groups.map((group) => (
                            <TabsTrigger key={group.key} value={group.key}>
                                {group.label} ({group.members.length})
                            </TabsTrigger>
                        ))}
                    </TabsList>
                    {groups.map((group) => (
                        <TabsContent key={group.key} value={group.key}>
                            <MemberGrid members={group.members} />
                        </TabsContent>
                    ))}
                </Tabs>
            </div>
        </>
    );
}
