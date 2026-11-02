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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { MemberItem } from './member-row';

/**
 * Ownership transfer (PRD 5.3): owner-only action handing the Owner role
 * to another member. The acting user drops to Admin on success.
 */
export default function TransferOwnershipDialog({
    organizationId,
    members,
}: {
    organizationId: number;
    members: MemberItem[];
}) {
    const [open, setOpen] = useState(false);
    const [successorId, setSuccessorId] = useState<string>('');

    const successors = members.filter((member) => member.role !== 'owner');

    const transfer = () => {
        if (!successorId) {
            return;
        }

        router.post(
            `/organizations/${organizationId}/transfer-ownership`,
            { user_id: successorId },
            {
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Transfer ownership
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Transfer ownership</DialogTitle>
                    <DialogDescription>
                        Hand the Owner role to another member. You will become
                        an Admin.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-1.5">
                    <Select value={successorId} onValueChange={setSuccessorId}>
                        <SelectTrigger className="w-full">
                            <SelectValue placeholder="Choose a member" />
                        </SelectTrigger>
                        <SelectContent>
                            {successors.map((member) => (
                                <SelectItem
                                    key={member.id}
                                    value={String(member.id)}
                                >
                                    {member.user.name} · {member.user.email}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {successors.length === 0 && (
                        <p className="text-muted-foreground text-xs">
                            Invite another member first — ownership can only
                            move to an existing member.
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <Button onClick={transfer} disabled={!successorId}>
                        Transfer ownership
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
