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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';

export type ProjectItem = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    creator: string | null;
    createdAt: string;
};

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    active: 'default',
    paused: 'secondary',
    archived: 'outline',
};

export default function ProjectCard({
    project,
    canManage,
}: {
    project: ProjectItem;
    canManage: boolean;
}) {
    const changeStatus = (status: string) => {
        router.patch(
            `/projects/${project.id}`,
            { status },
            { preserveScroll: true },
        );
    };

    const deleteProject = () => {
        router.delete(`/projects/${project.id}`, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader className="flex-row items-start justify-between space-y-0">
                <div>
                    <CardTitle className="text-base">{project.name}</CardTitle>
                    {project.description && (
                        <CardDescription className="line-clamp-2">
                            {project.description}
                        </CardDescription>
                    )}
                </div>
                <Badge variant={STATUS_VARIANT[project.status] ?? 'outline'}>
                    {project.status}
                </Badge>
            </CardHeader>

            <CardContent className="space-y-4">
                <div className="text-muted-foreground text-xs">
                    by {project.creator ?? 'unknown'} ·{' '}
                    {new Date(project.createdAt).toLocaleDateString()}
                </div>

                {canManage && (
                    <div className="flex items-center gap-2">
                        <Select
                            value={project.status}
                            onValueChange={changeStatus}
                        >
                            <SelectTrigger className="h-8 w-32 capitalize">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {['active', 'paused', 'archived'].map(
                                    (status) => (
                                        <SelectItem
                                            key={status}
                                            value={status}
                                            className="capitalize"
                                        >
                                            {status}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>

                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive"
                                >
                                    Delete
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>
                                        Delete {project.name}?
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        This removes the project permanently.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>
                                        Cancel
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        onClick={deleteProject}
                                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                    >
                                        Delete
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
