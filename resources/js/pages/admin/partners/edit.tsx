import PartnerForm from '@/pages/admin/partners/partner-form';
import type { Partner } from '@/types/models';

export default function EditPartner({ item }: { item: Partner }) {
    return <PartnerForm item={item} />;
}
