import PostForm from '@/pages/admin/posts/post-form';
import type { Post } from '@/types/models';

export default function EditPost({
    item,
    categories,
    authors,
}: {
    item: Post;
    categories: { id: number; name: string }[];
    authors: { id: number; name: string }[];
}) {
    return <PostForm item={item} categories={categories} authors={authors} />;
}
