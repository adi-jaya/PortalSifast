import { Link } from '@inertiajs/react';
import { Ticket, AlertTriangle, Users } from 'lucide-react';
import { IconWell } from '@/components/icon-well';

const actions = [
    { title: 'Tiket Saya', href: '/tickets?assignee=me', icon: Ticket, variant: 'brand' as const },
    { title: 'Belum Ditugaskan', href: '/tickets?unassigned=1', icon: Users, variant: 'soft' as const },
    { title: 'SLA Warning', href: '/tickets?sla_warning=1', icon: AlertTriangle, variant: 'danger' as const },
];

export function QuickActionsBar() {
    return (
        <div className="flex flex-wrap gap-2">
            {actions.map(({ title, href, icon, variant }) => (
                <Link
                    key={href}
                    href={href}
                    className="flex min-h-11 cursor-pointer items-center gap-2.5 rounded-xl border border-border bg-card px-3 py-2 text-sm font-medium shadow-sm transition-colors duration-200 hover:border-primary/30 hover:bg-secondary"
                >
                    <IconWell icon={icon} size="sm" variant={variant} />
                    {title}
                </Link>
            ))}
        </div>
    );
}
