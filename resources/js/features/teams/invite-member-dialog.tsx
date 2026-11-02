import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

const schema = z.object({
    email: z.string().email(),
    role: z.enum(['admin', 'member', 'viewer']),
});

type FormData = z.infer<typeof schema>;

/**
 * Invite-by-email dialog (PRD 5.3). Owned by the organization settings
 * screen; only rendered for users able to manage members.
 */
export default function InviteMemberDialog({
    organizationId,
}: {
    organizationId: number;
}) {
    const [open, setOpen] = useState(false);

    const {
        register,
        handleSubmit,
        setValue,
        setError,
        watch,
        reset,
        formState: { errors, isSubmitting },
    } = useForm<FormData>({
        resolver: zodResolver(schema),
        defaultValues: { email: '', role: 'member' },
    });

    const submit = handleSubmit((data) => {
        router.post(`/organizations/${organizationId}/invitations`, data, {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
            onError: (serverErrors) => {
                if (serverErrors.email) {
                    setError('email', { message: serverErrors.email });
                }
                if (serverErrors.role) {
                    setError('role', { message: serverErrors.role });
                }
            },
        });
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">Invite member</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite a member</DialogTitle>
                    <DialogDescription>
                        They&apos;ll receive an email with a link to join the
                        organization.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="invite-email">Email</Label>
                        <Input
                            id="invite-email"
                            type="email"
                            placeholder="teammate@example.com"
                            {...register('email')}
                        />
                        <FormError message={errors.email?.message} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Role</Label>
                        <Select
                            value={watch('role')}
                            onValueChange={(value) =>
                                setValue(
                                    'role',
                                    value as 'admin' | 'member' | 'viewer',
                                )
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="admin">Admin</SelectItem>
                                <SelectItem value="member">Member</SelectItem>
                                <SelectItem value="viewer">Viewer</SelectItem>
                            </SelectContent>
                        </Select>
                        <FormError message={errors.role?.message} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={isSubmitting}>
                            Send invitation
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
