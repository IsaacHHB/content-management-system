import EventForm from '@/pages/admin/events/event-form';
import type { NdnEvent } from '@/types/models';

export default function EditEvent({ item }: { item: NdnEvent }) {
    return <EventForm item={item} />;
}
