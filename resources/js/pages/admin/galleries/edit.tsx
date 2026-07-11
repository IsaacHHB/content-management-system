import GalleryForm from '@/pages/admin/galleries/gallery-form';
import type { Gallery } from '@/types/models';

export default function EditGallery({ item }: { item: Gallery }) {
    return <GalleryForm item={item} />;
}
