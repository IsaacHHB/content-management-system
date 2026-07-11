import PageForm from '@/pages/admin/pages/page-form';
import type { Page } from '@/types/models';

export default function EditPage({
    item,
    parentOptions,
}: {
    item: Page;
    parentOptions: { id: number; title: string }[];
}) {
    return <PageForm item={item} parentOptions={parentOptions} />;
}
