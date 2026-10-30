import { Head, router } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';
import { formatCurrency, formatDate, formatDateTime } from '@/lib/format';
import { approve, index, reject } from '@/routes/deletion-requests';

type DeletionRequestRow = {
    id: number;
    type: 'sale' | 'cash_flow' | 'customer' | 'customer_payment';
    label: string | null;
    amount: number | null;
    occurred_at: string | null;
    station: string | null;
    requested_by: string | null;
    reason: string;
    requested_at: string;
};

type DeletionRequestsProps = {
    requests: DeletionRequestRow[];
};

export default function DeletionRequestsIndex({
    requests,
}: DeletionRequestsProps) {
    const { t } = useLocale();
    const [reviewing, setReviewing] = useState(false);

    const review = (url: string) => {
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => setReviewing(true),
                onFinish: () => setReviewing(false),
            },
        );
    };

    const approveRequest = (request: DeletionRequestRow) => {
        if (confirm(t('deletion_requests.approve_confirm'))) {
            review(approve({ deletionRequest: request.id }).url);
        }
    };

    const rejectRequest = (request: DeletionRequestRow) => {
        review(reject({ deletionRequest: request.id }).url);
    };

    return (
        <>
            <Head title={t('deletion_requests.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h2 className="text-lg font-medium">
                        {t('deletion_requests.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {t('deletion_requests.description')}
                    </p>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>
                                {t('deletion_requests.record')}
                            </TableHead>
                            <TableHead>
                                {t('deletion_requests.requested_by')}
                            </TableHead>
                            <TableHead>
                                {t('deletion_requests.reason')}
                            </TableHead>
                            <TableHead className="text-right">
                                {t('common.actions')}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {requests.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="text-center text-muted-foreground"
                                >
                                    {t('deletion_requests.empty')}
                                </TableCell>
                            </TableRow>
                        )}
                        {requests.map((request) => (
                            <TableRow key={request.id}>
                                <TableCell>
                                    <div className="flex flex-col gap-1">
                                        <Badge
                                            variant="secondary"
                                            className="w-fit"
                                        >
                                            {t(
                                                `deletion_requests.type_${request.type}`,
                                            )}
                                        </Badge>
                                        <span className="font-medium">
                                            {request.label ?? '—'}
                                        </span>
                                        {(request.amount !== null ||
                                            request.occurred_at) && (
                                            <span className="text-xs text-muted-foreground tabular-nums">
                                                {request.amount !== null &&
                                                    formatCurrency(
                                                        request.amount,
                                                    )}
                                                {request.amount !== null &&
                                                    request.occurred_at &&
                                                    ' · '}
                                                {request.occurred_at &&
                                                    (request.type ===
                                                    'cash_flow'
                                                        ? formatDate(
                                                              request.occurred_at,
                                                          )
                                                        : formatDateTime(
                                                              request.occurred_at,
                                                          ))}
                                            </span>
                                        )}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-col gap-1">
                                        <span>
                                            {request.requested_by ?? '—'}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {request.station ?? '—'}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDateTime(
                                                request.requested_at,
                                            )}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell className="min-w-48 whitespace-normal">
                                    {request.reason}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={reviewing}
                                            onClick={() =>
                                                rejectRequest(request)
                                            }
                                        >
                                            <X className="h-4 w-4" />
                                            {t('deletion_requests.reject')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            disabled={reviewing}
                                            onClick={() =>
                                                approveRequest(request)
                                            }
                                        >
                                            <Check className="h-4 w-4" />
                                            {t('deletion_requests.approve')}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}

DeletionRequestsIndex.layout = {
    breadcrumbs: [{ title: 'deletion_requests.title', href: index().url }],
};
