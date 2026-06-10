import type { LucideIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type StatCardProps = {
    title: string;
    value: string;
    icon?: LucideIcon;
    hint?: string;
};

export function StatCard({ title, value, icon: Icon, hint }: StatCardProps) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2 space-y-0 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                {Icon && (
                    <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />
                )}
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold tracking-tight tabular-nums">
                    {value}
                </div>
                {hint && (
                    <p className="mt-1 text-xs text-muted-foreground">{hint}</p>
                )}
            </CardContent>
        </Card>
    );
}
