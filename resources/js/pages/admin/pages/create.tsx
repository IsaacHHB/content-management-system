import PageForm from '@/pages/admin/pages/page-form';

export default function CreatePage({
    parentOptions,
}: {
    parentOptions: { id: number; title: string }[];
}) {
    return <PageForm parentOptions={parentOptions} />;
}
