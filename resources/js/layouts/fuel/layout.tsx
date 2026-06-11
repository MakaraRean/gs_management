import { Link } from '@inertiajs/react';
import { Droplets, Fuel, Gauge, Truck } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { useLocale } from '@/hooks/use-locale';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { index as deliveriesIndex } from '@/routes/fuel/deliveries';
import { index as pumpsIndex } from '@/routes/fuel/pumps';
import { index as tanksIndex } from '@/routes/fuel/tanks';
import { index as typesIndex } from '@/routes/fuel/types';
import type { NavItem } from '@/types';

export default function FuelLayout({ children }: PropsWithChildren) {
    const { t } = useLocale();
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const sidebarNavItems: NavItem[] = [
        { title: t('fuel.tanks_title'), href: tanksIndex(), icon: Gauge },
        { title: t('fuel.pumps_title'), href: pumpsIndex(), icon: Fuel },
        { title: t('fuel.types_title'), href: typesIndex(), icon: Droplets },
        { title: t('fuel.deliveries_title'), href: deliveriesIndex(), icon: Truck },
    ];

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('fuel.management_title')}
                description={t('fuel.management_description')}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full lg:w-48">
                    <nav
                        className="flex flex-row flex-wrap gap-1 lg:flex-col lg:space-y-1"
                        aria-label="Fuel management"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('justify-start', {
                                    'bg-muted': isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="min-w-0 flex-1">{children}</div>
            </div>
        </div>
    );
}
