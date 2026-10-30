import { useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/hooks/use-locale';
import { usePermissions } from '@/hooks/use-permissions';

type DeleteRecordActionProps = {
    /** Whether a delete request for this record is already waiting for an admin. */
    pending: boolean;
    /** Where members without delete rights send their delete request. */
    requestUrl: string;
    onDelete: () => void;
    /** Icon-only delete button, for table rows. */
    compact?: boolean;
};

/**
 * Admins delete the record directly; everyone else asks an admin to delete it.
 */
export function DeleteRecordAction({
    pending,
    requestUrl,
    onDelete,
    compact = false,
}: DeleteRecordActionProps) {
    const { t } = useLocale();
    const { can } = usePermissions();
    const [requestOpen, setRequestOpen] = useState(false);
    const requestForm = useForm({ reason: '' });

    if (can('delete_records')) {
        return (
            <Button
                variant="ghost"
                size={compact ? 'icon' : 'sm'}
                className="text-destructive"
                onClick={onDelete}
            >
                <Trash2 className="h-4 w-4" />
                {!compact && t('common.delete')}
            </Button>
        );
    }

    if (pending) {
        return (
            <Badge variant="outline">{t('deletion_requests.pending')}</Badge>
        );
    }

    const openRequest = () => {
        requestForm.setData({ reason: '' });
        requestForm.clearErrors();
        setRequestOpen(true);
    };

    const submitRequest = (event: React.FormEvent) => {
        event.preventDefault();
        requestForm.post(requestUrl, {
            preserveScroll: true,
            onSuccess: () => setRequestOpen(false),
        });
    };

    return (
        <>
            <Button
                variant="ghost"
                size="sm"
                className="text-muted-foreground"
                onClick={openRequest}
            >
                <Trash2 className="h-4 w-4" />
                {t('deletion_requests.request')}
            </Button>

            <Dialog open={requestOpen} onOpenChange={setRequestOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {t('deletion_requests.request')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('deletion_requests.request_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitRequest} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="deletion_reason">
                                {t('deletion_requests.reason')}
                            </Label>
                            <Input
                                id="deletion_reason"
                                value={requestForm.data.reason}
                                onChange={(e) =>
                                    requestForm.setData(
                                        'reason',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={requestForm.errors.reason} />
                        </div>
                        <DialogFooter>
                            <Button
                                type="submit"
                                disabled={requestForm.processing}
                            >
                                {t('deletion_requests.send')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
