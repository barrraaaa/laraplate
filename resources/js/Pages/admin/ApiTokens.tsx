import AdminLayout from '@/components/layouts/admin-layout';
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
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { PageProps } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { KeyRound, Trash2 } from 'lucide-react';

type ApiTokensProps = {
    tokens: {
        data: {
            id: number;
            name: string;
            abilities: string[];
            owner: string;
            ownerEmail: string | null;
            lastUsedAt: string | null;
            createdAt: string;
        }[];
        current_page: number;
        last_page: number;
    };
};

/**
 * Admin overview of every personal API token (blueprint screen).
 */
export default function ApiTokens() {
    const { tokens } = usePage<PageProps<ApiTokensProps>>().props;

    return (
        <AdminLayout>
            <Head title="Admin — API Tokens" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        API tokens
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        All personal access tokens on the platform — revoke any
                        of them.
                    </p>
                </div>

                <Card>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Token
                                    </TableHead>
                                    <TableHead>Owner</TableHead>
                                    <TableHead>Abilities</TableHead>
                                    <TableHead className="text-right">
                                        Last used
                                    </TableHead>
                                    <TableHead className="pr-6" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tokens.data.map((token) => (
                                    <TableRow key={token.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-2 text-sm font-medium">
                                                <KeyRound className="text-muted-foreground size-4" />
                                                {token.name}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {token.owner}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {token.ownerEmail}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {token.abilities.map(
                                                    (ability) => (
                                                        <span
                                                            key={ability}
                                                            className="bg-muted text-muted-foreground rounded px-1.5 py-0.5 text-[10px] font-medium uppercase"
                                                        >
                                                            {ability}
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground text-right text-xs">
                                            {token.lastUsedAt
                                                ? new Date(
                                                      token.lastUsedAt,
                                                  ).toLocaleDateString()
                                                : 'Never'}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Revoke ${token.name}`}
                                                    >
                                                        <Trash2 className="text-destructive size-4" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>
                                                            Revoke {token.name}?
                                                        </AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            API requests using
                                                            this token fail
                                                            immediately.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>
                                                            Cancel
                                                        </AlertDialogCancel>
                                                        <AlertDialogAction
                                                            onClick={() =>
                                                                router.delete(
                                                                    `/admin/api-tokens/${token.id}`,
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        >
                                                            Revoke
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {tokens.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No tokens yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
