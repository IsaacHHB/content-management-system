import PostForm from '@/pages/admin/posts/post-form';

export default function CreatePost({
    categories,
    authors,
}: {
    categories: { id: number; name: string }[];
    authors: { id: number; name: string }[];
}) {
    return <PostForm categories={categories} authors={authors} />;
}
