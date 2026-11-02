import AppLayout from '@/components/layouts/app-layout';
import FormError from '@/components/shared/form-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { useState } from 'react';

type TokenItem = {
    id: number;
    name: string;
    abilities: string[];
    lastUsedAt: string | null;
    createdAt: string;
};

type ApiTokensProps = {
    tokens: TokenItem[];
    abilities: string[];
    plainTextToken?: string;
};

/**
 * Personal API tokens (PRD 5.8): Sanctum-backed with selectable
 * abilities. The plain-text token is shown exactly once.
 */
export default function ApiTokens() {
    const { tokens, abilities, plainTextToken } =
        usePage<PageProps<ApiTokensProps>>().props;

    return (
        <AppLayout>
            <Head title="API Tokens" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            API tokens
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Personal access tokens for the JSON API.
                        </p>
                    </div>
                    <CreateTokenDialog abilities={abilities} />
                </div>

                {plainTextToken && (
                    <Card className="border-emerald-600/40 dark:border-emerald-400/40">
                        <CardContent className="p-5">
                            <p className="text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                Copy your new token now — it won&apos;t be shown
                                again.
                            </p>
                            <code className="bg-muted mt-2 block rounded-md p-3 font-mono text-xs break-all">
                                {plainTextToken}
                            </code>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Your tokens</CardTitle>
                        <CardDescription>
                            Revoke a token to immediately cut off its API
                            access.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Name</TableHead>
                                    <TableHead>Abilities</TableHead>
                                    <TableHead className="text-right">
                                        Last used
                                    </TableHead>
                                    <TableHead className="pr-6" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tokens.map((token) => (
                                    <TableRow key={token.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-2 text-sm font-medium">
                                                <KeyRound className="text-muted-foreground size-4" />
                                                {token.name}
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
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Revoke ${token.name}`}
                                                onClick={() =>
                                                    router.delete(
                                                        `/settings/api-tokens/${token.id}`,
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2 className="text-destructive size-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {tokens.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="text-muted-foreground py-10 text-center text-sm"
                                        >
                                            No tokens yet — create your first
                                            one.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function CreateTokenDialog({ abilities }: { abilities: string[] }) {
    const [open, setOpen] = useState(false);
    const [name, setName] = useState('');
    const [selected, setSelected] = useState<string[]>(['read']);
    const [error, setError] = useState<string | undefined>();

    const toggle = (ability: string) => {
        setSelected((current) =>
            current.includes(ability)
                ? current.filter((item) => item !== ability)
                : [...current, ability],
        );
    };

    const create = () => {
        router.post(
            '/settings/api-tokens',
            { name, abilities: selected },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setName('');
                    setSelected(['read']);
                    setOpen(false);
                },
                onError: (serverErrors) => setError(serverErrors.name),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm">Create token</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create API token</DialogTitle>
                    <DialogDescription>
                        Tokens authenticate requests to the JSON API with a
                        Bearer header.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="token-name">Token name</Label>
                        <Input
                            id="token-name"
                            value={name}
                            placeholder="CI deploy"
                            onChange={(e) => setName(e.target.value)}
                        />
                        <FormError message={error} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Abilities</Label>
                        <div className="flex flex-wrap gap-2">
                            {abilities.map((ability) => (
                                <button
                                    key={ability}
                                    type="button"
                                    onClick={() => toggle(ability)}
                                    className={
                                        selected.includes(ability)
                                            ? 'bg-primary text-primary-foreground rounded-full border px-3 py-1 text-xs font-medium'
                                            : 'hover:bg-secondary rounded-full border px-3 py-1 text-xs font-medium'
                                    }
                                >
                                    {ability}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        onClick={create}
                        disabled={name.trim() === '' || selected.length === 0}
                    >
                        Create token
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
