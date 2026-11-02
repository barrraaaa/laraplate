import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';

export type MemberItem = {
    id: number;
    role: string;
    user: { name: string; email: string };
};

/**
 * One member row in the organization settings (PRD 5.3). Role changes and
 * removals POST through the standard member routes.
 */
export default function MemberRow({
    member,
    organizationId,
    canManage,
}: {
    member: MemberItem;
    organizationId: number;
    canManage: boolean;
}) {
    const changeRole = (role: string) => {
        if (role === member.role) {
            return;
        }

        router.patch(
            `/organizations/${organizationId}/members/${member.id}`,
            { role },
            { preserveScroll: true },
        );
    };

    const remove = () => {
        router.delete(`/organizations/${organizationId}/members/${member.id}`, {
            preserveScroll: true,
        });
    };

    const editable = canManage && member.role !== 'owner';

    return (
        <div className="flex items-center justify-between gap-4 py-3">
            <div className="flex min-w-0 items-center gap-3">
                <Avatar className="size-9">
                    <AvatarFallback className="text-xs">
                        {member.user.name.charAt(0).toUpperCase()}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0">
                    <div className="flex items-center gap-2">
                        <span className="truncate text-sm font-medium">
                            {member.user.name}
                        </span>
                        {member.role === 'owner' && (
                            <span className="bg-primary/10 text-primary rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase">
                                Owner
                            </span>
                        )}
                    </div>
                    <div className="text-muted-foreground truncate text-xs">
                        {member.user.email}
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-2">
                {editable ? (
                    <Select value={member.role} onValueChange={changeRole}>
                        <SelectTrigger className="h-8 w-28 capitalize">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {['admin', 'member', 'viewer'].map((role) => (
                                <SelectItem
                                    key={role}
                                    value={role}
                                    className="capitalize"
                                >
                                    {role}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <span className="text-muted-foreground w-28 text-right text-xs capitalize">
                        {member.role}
                    </span>
                )}

                {editable && (
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive"
                            >
                                Remove
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Remove {member.user.name}?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    They will immediately lose access to this
                                    organization and its data.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={remove}
                                    className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                >
                                    Remove
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                )}
            </div>
        </div>
    );
}
