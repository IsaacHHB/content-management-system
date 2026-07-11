import TeamMemberForm from '@/pages/admin/team/team-form';
import type { TeamMember } from '@/types/models';

export default function EditTeamMember({ item }: { item: TeamMember }) {
    return <TeamMemberForm item={item} />;
}
