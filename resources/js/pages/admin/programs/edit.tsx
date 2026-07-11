import ProgramForm from '@/pages/admin/programs/program-form';
import type { Program } from '@/types/models';

export default function EditProgram({ item }: { item: Program }) {
    return <ProgramForm item={item} />;
}
